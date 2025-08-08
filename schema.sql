SET SQL_MODE="NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";


CREATE TABLE IF NOT EXISTS `blogcomments` (
  `id` int(11) NOT NULL auto_increment,
  `toid` int(11) NOT NULL,
  `author` varchar(255) NOT NULL,
  `text` varchar(500) NOT NULL,
  `date` datetime NOT NULL,
  `parent_id` int(11) default NULL,
  PRIMARY KEY  (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `blogs`
--

CREATE TABLE IF NOT EXISTS `blogs` (
  `id` int(11) NOT NULL auto_increment,
  `text` text NOT NULL,
  `author` varchar(255) NOT NULL,
  `date` datetime NOT NULL,
  `title` varchar(255) NOT NULL,
  `category` int(11) NOT NULL,
  `privacy_level` int(11) NOT NULL,
  `pinned` tinyint(1) NOT NULL default '0',
  `kudos` int(11) default '0',
  PRIMARY KEY  (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `bulletincomments`
--

CREATE TABLE IF NOT EXISTS `bulletincomments` (
  `id` int(11) NOT NULL auto_increment,
  `toid` int(11) NOT NULL,
  `author` varchar(255) NOT NULL,
  `text` varchar(500) NOT NULL,
  `date` datetime NOT NULL,
  `parent_id` int(11) NOT NULL,
  PRIMARY KEY  (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `bulletins`
--

CREATE TABLE IF NOT EXISTS `bulletins` (
  `id` int(11) NOT NULL auto_increment,
  `text` text NOT NULL,
  `author` varchar(255) NOT NULL,
  `date` datetime NOT NULL,
  `title` varchar(255) NOT NULL,
  PRIMARY KEY  (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `comments`
--

CREATE TABLE IF NOT EXISTS `comments` (
  `id` int(11) NOT NULL auto_increment,
  `toid` int(11) NOT NULL,
  `author` varchar(255) NOT NULL,
  `text` varchar(500) NOT NULL,
  `date` datetime NOT NULL,
  `parent_id` int(11) NOT NULL,
  PRIMARY KEY  (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `favorites`
--

CREATE TABLE IF NOT EXISTS `favorites` (
  `user_id` int(11) NOT NULL,
  `favorites` text,
  PRIMARY KEY  (`user_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `friends`
--

CREATE TABLE IF NOT EXISTS `friends` (
  `id` int(11) NOT NULL auto_increment,
  `sender` varchar(255) NOT NULL,
  `receiver` varchar(255) NOT NULL,
  `status` varchar(255) NOT NULL default 'PENDING',
  PRIMARY KEY  (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `groupcomments`
--

CREATE TABLE IF NOT EXISTS `groupcomments` (
  `id` int(11) NOT NULL auto_increment,
  `toid` int(11) NOT NULL,
  `author` varchar(255) NOT NULL,
  `text` varchar(500) NOT NULL,
  `date` datetime NOT NULL,
  PRIMARY KEY  (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `groups`
--

CREATE TABLE IF NOT EXISTS `groups` (
  `id` int(11) NOT NULL auto_increment,
  `name` varchar(255) NOT NULL,
  `description` varchar(500) NOT NULL,
  `author` varchar(255) NOT NULL,
  `date` datetime NOT NULL,
  `members` text NOT NULL,
  PRIMARY KEY  (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `layoutcomments`
--

CREATE TABLE IF NOT EXISTS `layoutcomments` (
  `id` int(11) NOT NULL auto_increment,
  `toid` int(11) NOT NULL,
  `author` varchar(255) NOT NULL,
  `text` varchar(500) NOT NULL,
  `date` datetime NOT NULL,
  PRIMARY KEY  (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `layouts`
--

CREATE TABLE IF NOT EXISTS `layouts` (
  `id` int(11) NOT NULL auto_increment,
  `text` text NOT NULL,
  `author` varchar(255) NOT NULL,
  `date` datetime NOT NULL,
  `title` varchar(255) NOT NULL,
  `code` blob NOT NULL,
  PRIMARY KEY  (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--

CREATE TABLE IF NOT EXISTS `messages` (
  `id` int(11) NOT NULL auto_increment,
  `sender_id` int(11) NOT NULL,
  `receiver_id` int(11) NOT NULL,
  `subject` varchar(255) NOT NULL,
  `body` text NOT NULL,
  `sent_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `read_at` datetime DEFAULT NULL,
  PRIMARY KEY  (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `reports`
--

CREATE TABLE IF NOT EXISTS `reports` (
  `id` int(11) NOT NULL auto_increment,
  `reported_id` int(11) NOT NULL,
  `type` varchar(20) NOT NULL,
  `reason` text NOT NULL,
  `reporter_id` int(11) NOT NULL,
  `status` varchar(20) NOT NULL default 'open',
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `sessions`
--

CREATE TABLE IF NOT EXISTS `sessions` (
  `id` int(11) NOT NULL auto_increment,
  `session_id` varchar(16) NOT NULL,
  `user_id` int(11) NOT NULL,
  `user` varchar(50) NOT NULL,
  `last_logon` timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP,
  `last_activity` timestamp NOT NULL default '0000-00-00 00:00:00',
  `active` tinyint(1) NOT NULL,
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE IF NOT EXISTS `users` (
  `id` int(11) NOT NULL auto_increment,
  `rank` tinyint(4) NOT NULL default '0' COMMENT '0=member,1=global_mod,2=admin',
  `username` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `date` datetime NOT NULL,
  `bio` varchar(500) NOT NULL default '',
  `interests` varchar(500) NOT NULL default ' ',
  `css` blob NOT NULL,
  `music` varchar(255) NOT NULL default 'default.mp3',
  `pfp` varchar(255) NOT NULL default 'default.jpg',
  `currentgroup` varchar(255) NOT NULL default 'None',
  `status` varchar(255) NOT NULL default '',
  `private` tinyint(1) NOT NULL default '0',
  `views` int(11) NOT NULL default '0',
  `lastactive` datetime NOT NULL,
  `lastlogon` datetime NOT NULL,
  `banned_until` datetime DEFAULT NULL,
  PRIMARY KEY  (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;

-- --------------------------------------------------------
--
-- Table structure for table `forum_categories`
--
CREATE TABLE IF NOT EXISTS `forum_categories` (
  `id` int(11) NOT NULL auto_increment,
  `name` varchar(255) NOT NULL,
  `position` int(11) NOT NULL default '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------
--
-- Table structure for table `forums`
--
CREATE TABLE IF NOT EXISTS `forums` (
  `id` int(11) NOT NULL auto_increment,
  `category_id` int(11) NOT NULL,
  `parent_forum_id` int(11) default NULL,
  `name` varchar(255) NOT NULL,
  `description` varchar(500) NOT NULL,
  `position` int(11) NOT NULL default '0',
  PRIMARY KEY (`id`),
  FOREIGN KEY (`category_id`) REFERENCES `forum_categories` (`id`),
  FOREIGN KEY (`parent_forum_id`) REFERENCES `forums` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------
--
-- Table structure for table `forum_topics`
--
CREATE TABLE IF NOT EXISTS `forum_topics` (
  `id` int(11) NOT NULL auto_increment,
  `forum_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `locked` tinyint(1) NOT NULL default '0',
  `sticky` TINYINT(1) DEFAULT 0,
  `moved_to` INT NULL,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`forum_id`) REFERENCES `forums` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------
--
-- Table structure for table `forum_posts`
--
CREATE TABLE IF NOT EXISTS `forum_posts` (
  `id` int(11) NOT NULL auto_increment,
  `topic_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `body` text NOT NULL,
  `created_at` datetime NOT NULL,
  `deleted` TINYINT(1) DEFAULT 0,
  `deleted_by` INT DEFAULT NULL,
  `deleted_at` DATETIME DEFAULT NULL,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`topic_id`) REFERENCES `forum_topics` (`id`),
  FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------
--
-- Table structure for table `forum_permissions`
--
CREATE TABLE IF NOT EXISTS `forum_permissions` (
  `forum_id` int(11) NOT NULL,
  `role` varchar(255) NOT NULL,
  `can_view` tinyint(1) NOT NULL default '0',
  `can_post` tinyint(1) NOT NULL default '0',
  `can_moderate` tinyint(1) NOT NULL default '0',
  PRIMARY KEY (`forum_id`, `role`),
  FOREIGN KEY (`forum_id`) REFERENCES `forums` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------
--
-- Table structure for table `forum_moderators`
--
CREATE TABLE IF NOT EXISTS `forum_moderators` (
  `id` int(11) NOT NULL auto_increment,
  `forum_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`forum_id`) REFERENCES `forums` (`id`),
  FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------
--
-- Seed data for forum testing
--
INSERT INTO `forum_categories` (`name`, `position`) VALUES ('General', 1);
INSERT INTO `forums` (`category_id`, `parent_forum_id`, `name`, `description`, `position`) VALUES (1, NULL, 'General Discussion', 'General topics and conversations', 1);

INSERT INTO `users` (`id`, `rank`, `username`, `email`, `password`, `date`, `bio`, `interests`, `css`, `music`, `pfp`, `currentgroup`, `status`, `private`, `views`, `lastactive`, `lastlogon`)
VALUES (1, 1, 'globalmod', 'globalmod@example.com', '$2y$12$ocAW8xAoEHay8ElZLzsFOuP5EM9t1YyGdslYQD/EXcNLLU1VmVGSS', NOW(), '', ' ', '', 'default.mp3', 'default.jpg', 'None', '', 0, 0, NOW(), NOW());

INSERT INTO `forum_moderators` (`forum_id`, `user_id`) VALUES (1, 1);

-- --------------------------------------------------------
--
-- Table structure for table `forum_user_settings`
--
CREATE TABLE IF NOT EXISTS `forum_user_settings` (
  `user_id` int(11) NOT NULL,
  `background_image_url` varchar(255) DEFAULT NULL,
  `background_color` varchar(7) DEFAULT NULL,
  `text_color` varchar(7) DEFAULT NULL,
  PRIMARY KEY (`user_id`),
  FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------
--
-- Table structure for table `notifications`
--
CREATE TABLE IF NOT EXISTS `notifications` (
  `id` int(11) NOT NULL auto_increment,
  `user_id` int(11) NOT NULL,
  `post_id` int(11) NOT NULL,
  `is_read` tinyint(1) NOT NULL default '0',
  `created_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`user_id`) REFERENCES `users` (`id`),
  FOREIGN KEY (`post_id`) REFERENCES `forum_posts` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------
--
-- Table structure for table `mod_log`
--
CREATE TABLE IF NOT EXISTS `mod_log` (
  `id` int(11) NOT NULL auto_increment,
  `moderator_id` int(11) NOT NULL,
  `action` varchar(255) NOT NULL,
  `target_type` varchar(255) NOT NULL,
  `target_id` int(11) NOT NULL,
  `timestamp` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`moderator_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------
--
-- Table structure for table `bad_words`
--
CREATE TABLE IF NOT EXISTS `bad_words` (
  `id` int(11) NOT NULL auto_increment,
  `word` varchar(255) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

INSERT INTO `bad_words` (`word`) VALUES ('badword'), ('evil');
