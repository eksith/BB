-- BB Installation SQL script
CREATE TABLE posts (
	id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, 
	topic_id INTEGER NOT NULL DEFAULT 0, 
	parent_id INTEGER NOT NULL DEFAULT 0, 
	title TEXT NOT NULL, 
	created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, 
	updated_at DATETIME DEFAULT NULL, 
	reply_at DATETIME DEFAULT NULL, 
	summary TEXT NOT NULL, 
	plain TEXT NOT NULL, 
	raw TEXT NOT NULL, 
	body TEXT NOT NULL, 
	reply_count INTEGER NOT NULL DEFAULT 0, 
	quality FLOAT NOT NULL DEFAULT 0, 
	status INTEGER NOT NULL DEFAULT 0, 
	auth_key VARCHAR NOT NULL,
	ip VARCHAR NOT NULL
);

CREATE INDEX idx_posts_on_created_at ON posts ( created_at );
CREATE INDEX idx_posts_on_updated_at ON posts ( updated_at );
CREATE INDEX idx_posts_on_reply_at ON posts ( reply_at );
CREATE INDEX idx_posts_on_quality ON posts ( quality );
CREATE INDEX idx_posts_on_status ON posts ( status );
CREATE INDEX idx_posts_on_ip ON posts ( ip );


CREATE VIRTUAL TABLE posts_search USING fts4 ( search_data );


CREATE TABLE posts_family (
	topic_id INTEGER NOT NULL, 
	parent_id INTEGER NOT NULL, 
	child_id INTEGER NOT NULL, 
	PRIMARY KEY ( topic_id, parent_id, child_id )
);


CREATE TABLE post_votes(
	post_id INTEGER NOT NULL, 
	session_id VARCHAR NOT NULL, 
	fingerprint VARCHAR NOT NULL, 
	vote INTEGER NOT NULL, 
	created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, 
	PRIMARY KEY ( post_id, session_id, fingerprint )
);

CREATE INDEX idx_post_votes_on_created_at ON post_votes ( created_at );


CREATE TABLE taxonomy (
	id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, 
	label VARCHAR NOT NULL, 
	term VARCHAR NOT NULL, 
	slug VARCHAR NOT NULL, 
	created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, 
	updated_at DATETIME DEFAULT NULL, 
	status INTEGER NOT NULL DEFAULT 0
);

CREATE UNIQUE INDEX idx_taxonomy_on_terms ON taxonomy ( label ASC, term ASC );
CREATE INDEX idx_taxonomy_on_status ON taxonomy ( status );


CREATE TABLE posts_taxonomy (
	post_id INTEGER NOT NULL, 
	taxonomy_id INTEGER NOT NULL, 
	PRIMARY KEY ( post_id, taxonomy_id ) 
);


CREATE TABLE actions (
	id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, 
	run INTEGER NOT NULL, 
	created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP 
);

CREATE INDEX idx_actions_on_created_at ON actions ( created_at );


-- Post triggers
CREATE TRIGGER post_after_insert AFTER INSERT ON posts FOR EACH ROW 
BEGIN
	INSERT INTO posts_search ( docid, search_data ) VALUES ( NEW.rowid, NEW.title || ' ' || NEW.plain );
	UPDATE posts SET updated_at = CURRENT_TIMESTAMP WHERE id = NEW.rowid;
END;

CREATE TRIGGER post_before_update BEFORE UPDATE ON posts FOR EACH ROW 
BEGIN
	DELETE FROM posts_search WHERE docid = OLD.rowid;
END;

CREATE TRIGGER post_after_update AFTER UPDATE ON posts FOR EACH ROW 
BEGIN
	INSERT INTO posts_search ( docid, search_data ) VALUES ( NEW.rowid, NEW.plain );
	UPDATE posts SET updated_at = CURRENT_TIMESTAMP WHERE id = NEW.rowid;
END;


--Post family triggers
CREATE TRIGGER new_topic AFTER INSERT ON posts
WHEN NEW.topic_id = 0 AND NEW.parent_id = 0
BEGIN
	INSERT INTO posts_family ( topic_id, parent_id, child_id ) 
		VALUES ( NEW.id, NEW.id, NEW.id );

END;

CREATE TRIGGER new_reply AFTER INSERT ON posts
WHEN NEW.parent_id = 0 AND NEW.topic_id <> 0 
BEGIN
	INSERT INTO posts_family ( topic_id, parent_id, child_id ) 
		VALUES ( NEW.topic_id, NEW.id, NEW.id );
END;

CREATE TRIGGER new_sub AFTER INSERT ON posts
WHEN NEW.topic_id = 0 AND NEW.parent_id <> 0
BEGIN
	INSERT INTO posts_family ( topic_id, parent_id, child_id ) 
		VALUES ( (
			SELECT topic_id FROM posts_family
				WHERE parent_id = NEW.parent_id
				LIMIT 1 
		), NEW.parent_id, NEW.id );
END;

CREATE TRIGGER post_family_after_insert AFTER INSERT ON posts_family FOR EACH ROW 
BEGIN
	UPDATE posts SET reply_count = ( reply_count + 1 ), reply_at = CURRENT_TIMESTAMP 
		WHERE id = NEW.root_id OR id = NEW.parent_id;
END;


-- Taxonomy procedures
CREATE TRIGGER taxonomy_after_insert AFTER INSERT ON taxonomy FOR EACH ROW 
BEGIN
	UPDATE taxonomy SET updated_at = CURRENT_TIMESTAMP WHERE id = NEW.rowid;
END;

CREATE TRIGGER taxonomy_after_update AFTER UPDATE ON taxonomy FOR EACH ROW 
BEGIN
	UPDATE taxonomy SET updated_at = CURRENT_TIMESTAMP WHERE id = NEW.rowid;
END;

CREATE TRIGGER taxonomy_before_delete BEFORE DELETE ON taxonomy FOR EACH ROW 
BEGIN
	DELETE FROM posts_taxonomy WHERE taxonomy_id = OLD.rowid;
	DELETE FROM taxonomy_family WHERE parent_id = OLD.rowid OR child_id = OLD.rowid;
END;


-- Post moderation action
CREATE TRIGGER actions_posts_after_insert AFTER INSERT ON actions FOR EACH ROW WHEN NEW.run = 0 
BEGIN
	DELETE FROM posts WHERE quality <= -99;
	UPDATE posts SET status = 1 WHERE quality > 1;
	UPDATE posts SET status = -1 WHERE quality < -1;
END;


-- Voting
CREATE TRIGGER post_vote_after_insert AFTER INSERT ON post_votes FOR EACH ROW
BEGIN
	UPDATE posts SET quality = ROUND( 
		( quality + ( NEW.vote / strftime( '%s', 'now' ) - strftime( '%s', created_at ) ) ), 4
	) WHERE id = NEW.post_id AND NEW.session_id NOT IN ( 
		SELECT session_id FROM post_votes WHERE post_votes.post_id = NEW.post_id
	);
END;


PRAGMA encoding = "UTF-8";
PRAGMA main.journal_mode = WAL;
PRAGMA main.secure_delete = TRUE;
