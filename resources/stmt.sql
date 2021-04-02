-- #!sqlite
-- #{ alias
-- #  { init
CREATE TABLE IF NOT EXISTS Players(
  Username VARCHAR NOT NULL,
  Data VARCHAR NOT NULL,
  PRIMARY KEY(Username)
);
-- #  }
-- #  { search
SELECT Data
FROM Players
WHERE Username=:username;
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
-- #  { save
-- #    :username string
-- #    :data string
UPDATE Players
SET Data=:data
WHERE Username=:username;
-- #  }
-- #}