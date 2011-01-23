CREATE TABLE IF NOT EXISTS `polls_questions` (
  `id` int(11) NOT NULL auto_increment,
  `question` text NOT NULL,
  `timeof` timestamp NOT NULL,
  `published`	set('yes','no') NOT NULL default 'yes',
  `view_results` tinyint(4) NOT NULL,
  PRIMARY KEY  (id)
);

CREATE TABLE IF NOT EXISTS `polls_answers` (
  id int(11) NOT NULL auto_increment,
  question_id int(11) NOT NULL,
  answer text NOT NULL,
  display_order int(11) NOT NULL,
  PRIMARY KEY  (id),
  KEY `question_id` (`question_id`),
  CONSTRAINT `polls_ibfk_1` FOREIGN KEY (`question_id`) REFERENCES `polls_questions` (`id`) ON DELETE CASCADE ON UPDATE CASCADE 
);


CREATE TABLE IF NOT EXISTS `polls_votes` (
  `id` int(11) NOT NULL auto_increment,
  `question_id` int(11) NOT NULL,
  `answer_id` int(11) NOT NULL,
  `timeof` timestamp NOT NULL,
  `user_id` int(11) NOT NULL,
  PRIMARY KEY  (id),
  KEY `question_id` (`question_id`),
  KEY `answer_id` (`answer_id`),
  CONSTRAINT `polls_votes_ibfk_1` FOREIGN KEY (`question_id`) REFERENCES `polls_questions` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `polls_votes_ibfk_2` FOREIGN KEY (`answer_id`) REFERENCES `polls_answers` (`id`) ON DELETE CASCADE ON UPDATE CASCADE 
   
  
);


INSERT INTO servertracker (servername, serveraddress, owner) VALUES ('bzexcess' , 'bzexcess.com:5432', 'blast');



