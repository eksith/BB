-- BB Installation SQL script
CREATE TABLE posts (
	id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, 
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
	root_id INTEGER NOT NULL, 
	parent_id INTEGER NOT NULL, 
	child_id INTEGER NOT NULL, 
	PRIMARY KEY ( root_id, parent_id, child_id )
);

CREATE TABLE post_votes(
	post_id INTEGER NOT NULL, 
	session_id VARCHAR NOT NULL, 
	vote INTEGER NOT NULL, 
	created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, 
	PRIMARY KEY ( post_id, session_id )
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

CREATE TABLE firewall(
	ip VARCHAR PRIMARY KEY NOT NULL, 
	created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, 
	expires_at DATETIME DEFAULT NULL, 
	response INTEGER NOT NULL DEFAULT 0
);

CREATE INDEX idx_firewall_on_created_at ON firewall ( created_at );
CREATE INDEX idx_firewall_on_expires_at ON firewall ( expires_at );


CREATE TABLE actions (
	id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, 
	run INTEGER NOT NULL, 
	created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP 
);

CREATE INDEX idx_actions_on_created_at ON sessions ( created_at );



CREATE TRIGGER post_after_insert AFTER INSERT ON posts FOR EACH ROW 
BEGIN
	INSERT INTO posts_search ( docid, search_data ) VALUES ( NEW.rowid, New.title || ' ' || NEW.plain );
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


CREATE TRIGGER post_family_after_insert AFTER INSERT ON posts_family FOR EACH ROW 
BEGIN
	UPDATE posts SET reply_count = ( reply_count + 1 ), reply_at = CURRENT_TIMESTAMP 
		WHERE id = NEW.root_id OR id = NEW.parent_id;
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
END;


PRAGMA encoding = "UTF-8";
PRAGMA main.journal_mode = WAL;
PRAGMA main.secure_delete = TRUE;
