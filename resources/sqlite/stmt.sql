-- #!sqlite
-- #{ alias
-- #  { init
CREATE TABLE IF NOT EXISTS Players(
  Username VARCHAR NOT NULL,
  Data VARCHAR NOT NULL,
  PRIMARY KEY(Username)
);
-- #  }
-- #  { load
SELECT * FROM Players;
-- #  }
-- #  { register
-- #    :username string
-- #    :data string
INSERT OR REPLACE INTO Players(Username, Data)
VALUES (:username, :data);
-- #  }
-- #}