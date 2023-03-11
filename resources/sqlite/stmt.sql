-- #!sqlite
-- #{ alias
-- #  { init
-- #    { old_players
CREATE TABLE IF NOT EXISTS Players(
  Username VARCHAR NOT NULL,
  Data     VARCHAR NOT NULL,
  PRIMARY KEY(Username)
);
-- #    }
-- #    { known_players
CREATE TABLE IF NOT EXISTS KnownPlayers(
  Username VARCHAR(16) PRIMARY KEY
);
-- #    }
-- #    { address
CREATE TABLE IF NOT EXISTS Addresses(
  Username VARCHAR(16) NOT NULL,
  Address  VARCHAR(39) NOT NULL, -- Includes IPv4 and IPv6
  PRIMARY KEY(Username, Address),
  FOREIGN KEY(Username) REFERENCES KnownPlayers(Username) ON DELETE CASCADE
);
-- #    }
-- #    { client_random_id
CREATE TABLE IF NOT EXISTS ClientRandomIds(
  Username       VARCHAR(16) NOT NULL,
  ClientRandomId INTEGER     NOT NULL,
  PRIMARY KEY(Username, ClientRandomId),
  FOREIGN KEY(Username) REFERENCES KnownPlayers(Username) ON DELETE CASCADE
);
-- #    }
-- #    { device_id
CREATE TABLE IF NOT EXISTS DeviceIds(
  Username VARCHAR(16) NOT NULL,
  DeviceId VARCHAR(32) NOT NULL,
  PRIMARY KEY(Username, DeviceId),
  FOREIGN KEY(Username) REFERENCES KnownPlayers(Username) ON DELETE CASCADE
);
-- #    }
-- #    { xuid
CREATE TABLE IF NOT EXISTS XUIDs(
  Username VARCHAR(16) PRIMARY KEY,
  XUID     VARCHAR(16) NOT NULL,
  FOREIGN KEY(Username) REFERENCES KnownPlayers(Username) ON DELETE CASCADE
);
-- #    }
-- #  }
-- #  { load_old_players
SELECT *
FROM Players;
-- #  }
-- #  { delete_old_players
DROP TABLE Players;
-- #  }
-- #  { get_alt
-- #    { address
-- #      :username string
-- #      :extraAddress ?string
SELECT DISTINCT Username
FROM Addresses
WHERE Username != LOWER(:username) AND (Address IN (
  SELECT Address
  FROM Addresses
  WHERE Username = LOWER(:username)
) OR Address = :extraAddress);
-- #    }
-- #    { client_random_id
-- #      :username string
-- #      :extraClientRandomId ?int
SELECT DISTINCT Username
FROM ClientRandomIds
WHERE Username != LOWER(:username) AND (ClientRandomId IN (
  SELECT ClientRandomId
  FROM ClientRandomIds
  WHERE Username = LOWER(:username)
) OR ClientRandomId = :extraClientRandomId);
-- #    }
-- #    { device_id
-- #      :username string
-- #      :extraDeviceId ?string
SELECT DISTINCT Username
FROM DeviceIds
WHERE Username != LOWER(:username) AND (DeviceId IN (
  SELECT DeviceId
  FROM DeviceIds
  WHERE Username = LOWER(:username)
) OR DeviceId = :extraDeviceId);
-- #    }
-- #    { xuid
-- #      :username string
-- #      :extraXuid ?string
SELECT Username
FROM XUIDs
WHERE Username != LOWER(:username) AND (XUID = (
  SELECT XUID
  FROM XUIDs
  WHERE Username = LOWER(:username)
) OR XUID = :extraXuid);
-- #    }
-- #  }
-- #  { add
-- #    { known_player
-- #      :username string
INSERT OR IGNORE INTO KnownPlayers(Username) VALUES(LOWER(:username));
-- #    }
-- #    { address
-- #      :username string
-- #      :address string
INSERT OR IGNORE INTO Addresses(Username, Address)
VALUES(:username, :address);
-- #    }
-- #    { client_random_id
-- #      :username string
-- #      :clientRandomId int
INSERT OR IGNORE INTO ClientRandomIds(Username, ClientRandomId)
VALUES(:username, :clientRandomId);
-- #    }
-- #    { device_id
-- #      :username string
-- #      :deviceId string
INSERT OR IGNORE INTO DeviceIds(Username, DeviceId)
VALUES(:username, :deviceId);
-- #    }
-- #    { xuid
-- #      :username string
-- #      :xuid string
INSERT OR IGNORE INTO XUIDs(Username, XUID)
VALUES(:username, :xuid);
-- #    }
-- #  }
-- #}