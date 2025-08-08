<?php 
function login_check() {
    if (!isset($_SESSION['user'])) {
        header("Location: /login.php");
        exit;
    }

    require_once __DIR__ . '/users/user.php';
    if (isset($_SESSION['userId']) && isUserBanned($_SESSION['userId'])) {
        session_unset();
        session_destroy();
        header("Location: /login.php?msg=" . urlencode('Account banned'));
        exit;
    }
}

function admin_only() {
    if (!isset($_SESSION['userId']) || (defined('ADMIN_USER') && $_SESSION['userId'] != ADMIN_USER)) {
        header("Location: /admin/login.php?msg=" . urlencode('Admin access required'));
        exit;
    }
}

function csrf_token() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrf_token_input() {
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars(csrf_token(), ENT_QUOTES) . '">';
}

function csrf_verify() {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (!isset($_POST['csrf_token'], $_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
            die('Invalid CSRF token');
        }
        unset($_SESSION['csrf_token']);
    }
}

csrf_verify();

function validateContentHTML($validate) {
    // Whitelisted tags
    $allowedTags = '<a><b><big><blockquote><blink><br><center><code><del><details><div><em><font><h1><h2><h3><h4><h5><h6><hr><i><iframe><img><li><mark><marquee><ol><p><pre><small><span><strong><style><sub><summary><sup><table><td><th><time><tr><u><ul>';


    // Remove script tags
    $validated = preg_replace('/<script\b[^>]*>(.*?)<\/script>/is', '', $validate);
    
    // Remove PHP blocks
    $validated = preg_replace('/<\?php(.*?)\?>/is', '', $validated);

    // Remove any remaining PHP short tags
    $validated = preg_replace('/<\?(?!php)(.*?)\?>/is', '', $validated);
    
    // Remove behavior: url()
    $validated = str_replace("behavior: url", "", $validated);
    
    // Remove any remaining HTML tags except the allowed ones
    $validated = strip_tags($validated, $allowedTags);

    return $validated;
}

function validateLayoutHTML($html) {
    $allowedTags = [
        'style', 'img', 'div', 'iframe', 'a', 'h1', 'h2', 'h3', 'p', 'ul',
        'ol', 'li', 'blockquote', 'code', 'em', 'strong', 'br'
    ];
    $allowedAttrs = ['href', 'src', 'alt', 'title', 'style', 'width', 'height'];

    libxml_use_internal_errors(true);
    $dom = new DOMDocument();
    $dom->loadHTML('<?xml encoding="utf-8" ?>' . $html, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
    $xpath = new DOMXPath($dom);

    // Remove disallowed tags and attributes
    foreach ($xpath->query('//*') as $node) {
        if (!in_array($node->nodeName, $allowedTags)) {
            $node->parentNode->removeChild($node);
            continue;
        }

        if ($node->hasAttributes()) {
            $attrsToRemove = [];
            foreach ($node->attributes as $attr) {
                $name  = strtolower($attr->nodeName);
                $value = $attr->nodeValue;

                // Remove event handlers or non-whitelisted attributes
                if (strpos($name, 'on') === 0 || !in_array($name, $allowedAttrs)) {
                    $attrsToRemove[] = $attr->nodeName;
                    continue;
                }

                // Strip javascript: and data: URLs
                if (in_array($name, ['href', 'src']) && preg_match('/^\s*(javascript|data):/i', $value)) {
                    $attrsToRemove[] = $attr->nodeName;
                    continue;
                }

                // Sanitize inline CSS
                if ($name === 'style') {
                    $clean = preg_replace('/@import/i', '', $value);
                    $clean = preg_replace('/expression\s*\(/i', '', $clean);
                    $clean = preg_replace_callback('/url\s*\(([^\)]+)\)/i', function ($matches) {
                        $url = trim($matches[1], "'\" ");
                        return preg_match('/^javascript:/i', $url) ? '' : 'url(' . $url . ')';
                    }, $clean);
                    $attr->nodeValue = $clean;
                }
            }

            foreach ($attrsToRemove as $attrName) {
                $node->removeAttribute($attrName);
            }
        }
    }

    // Sanitize contents of <style> tags
    foreach ($xpath->query('//style') as $styleNode) {
        $css = $styleNode->textContent;
        $css = preg_replace('/@import[^;]*;/i', '', $css);
        $css = preg_replace('/expression\s*\([^;]*\)/i', '', $css);
        $css = preg_replace('/url\s*\(\s*javascript:[^\)]*\)/i', '', $css);
        $styleNode->textContent = $css;
    }

    $safe = $dom->saveHTML();
    libxml_clear_errors();
    return $safe;
}

// thanks dzhaugasharov https://gist.github.com/afsalrahim/bc8caf497a4b54c5d75d
function replaceBBcodes($text) {
    $text = htmlspecialchars($text);
    // BBcode array
    $find = array(
        '~\[b\](.*?)\[/b\]~s',
        '~\[i\](.*?)\[/i\]~s',
        '~\[u\](.*?)\[/u\]~s',
        '~\[quote=([^\]]+?)\](.*?)\[/quote\]~s',
        '~\[quote\](.*?)\[/quote\]~s',
        '~\[size=([^"><]*?)\](.*?)\[/size\]~s',
        '~\[color=([^"><]*?)\](.*?)\[/color\]~s',
        '~\[url\]((?:ftp|https?)://[^"><]*?)\[/url\]~s',
        '~\[img\](https?://[^"><]*?\.(?:jpg|jpeg|gif|png|bmp))\[/img\]~s'
    );
    // HTML tags to replace BBcode
    $replace = array(
        '<b>$1</b>',
        '<i>$1</i>',
        '<span style="text-decoration:underline;">$1</span>',
        '<blockquote><cite>$1 wrote:</cite>$2</blockquote>',
        '<blockquote>$1</blockquote>',
        '<span style="font-size:$1px;">$2</span>',
        '<span style="color:$1;">$2</span>',
        '<a href="$1">$1</a>',
        '<img src="$1" alt="" />'
    );
    // Replacing the BBcodes with corresponding HTML tags
    return preg_replace($find, $replace, $text);
}

function time_elapsed_string($datetime, $full = false) {
    $now = new DateTime;
    $ago = new DateTime($datetime);
    $diff = $now->diff($ago);

    // Calculate weeks as a separate variable using days
    $weeks = floor($diff->d / 7);
    $diff->d -= $weeks * 7;

    $string = array(
        'y' => 'year',
        'm' => 'month',
        'd' => 'day',
        'h' => 'hour',
        'i' => 'minute',
        's' => 'second',
    );

    if ($weeks) {
        $string = array_merge(array('w' => 'week'), $string); 
    }

    foreach ($string as $k => &$v) {
        $value = ($k === 'w') ? $weeks : $diff->$k; 
        if ($value) {
            $v = $value . ' ' . $v . ($value > 1 ? 's' : '');
        } else {
            unset($string[$k]);
        }
    }

    if (!$full) $string = array_slice($string, 0, 1);
    return $string ? implode(', ', $string) . ' ago' : 'just now';
}