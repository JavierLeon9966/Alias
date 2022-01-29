-- #!mysql
-- #{ alias
-- #  { init
CREATE TABLE IF NOT EXISTS Players(
  Username VARCHAR(16) NOT NULL,
  Data TEXT NOT NULL,
  PRIMARY KEY(Username)
);
-- #  }
-- #  { load
SELECT * FROM Players;
-- #  }
-- #  { register
-- #    :username string
-- #    :data string
INSERT INTO Players(Username, Data)
VALUES (:username, :data)
ON DUPLICATE KEY UPDATE Data = :data;
-- #  }
-- #}