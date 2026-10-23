ALTER TABLE inquiries
  MODIFY COLUMN kind ENUM('proposal','walkthrough','event_cleanup','move_out','other') NOT NULL DEFAULT 'proposal';
