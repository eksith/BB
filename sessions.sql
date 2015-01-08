-- Firewall and session database
CREATE TABLE sessions (
	id VARCHAR PRIMARY KEY NOT NULL, 
	skey VARCHAR NOT NULL, 
	created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, 
	updated_at DATETIME DEFAULT NULL, 
	data TEXT NOT NULL 
);

CREATE INDEX idx_sessions_on_created_at ON sessions ( created_at );
CREATE INDEX idx_sessions_on_updated_at ON sessions ( updated_at );


CREATE TABLE posts_sessions(
	post_id INTEGER NOT NULL, 
	session_id VARCHAR NOT NULL,
	fingerprint VARCHAR NOT NULL,
	created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, 
	PRIMARY KEY ( post_id, session_id, fingerprint )
);


CREATE TABLE firewall(
	id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, 
	ip DEFAULT VARCHAR NULL, 
	session_id VARCHAR NULL, 
	created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, 
	expires_at DATETIME DEFAULT NULL, 
	response INTEGER NOT NULL DEFAULT 0
);

CREATE INDEX idx_firewall_on_ip ON firewall ( ip );
CREATE INDEX idx_firewall_on_session ON firewall ( session_id );
CREATE INDEX idx_firewall_on_created_at ON firewall ( created_at );
CREATE INDEX idx_firewall_on_expires_at ON firewall ( expires_at );


CREATE TABLE actions (
	id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, 
	run INTEGER NOT NULL, 
	created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP 
);

CREATE INDEX idx_actions_on_created_at ON actions ( created_at );


-- Session insert procedures
CREATE TRIGGER session_after_insert AFTER INSERT ON sessions FOR EACH ROW BEGIN
	UPDATE sessions SET updated_at = CURRENT_TIMESTAMP WHERE id = NEW.rowid;
END;


-- Session moderation action
CREATE TRIGGER actions_flag_after_insert AFTER INSERT ON actions FOR EACH ROW WHEN NEW.run = 0  
BEGIN
	INSERT INTO banned_sessions ( id ) 
		SELECT session_id AS id FROM posts_sessions WHERE posts_sessions.post_id IN (
			SELECT post_id FROM posts_votes 
			JOIN posts ON posts_sessions.post_id = posts.id
			WHERE posts.quality < -1
		);
END;

PRAGMA encoding = "UTF-8";
PRAGMA main.journal_mode = WAL;
PRAGMA main.secure_delete = TRUE;
