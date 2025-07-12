-- #!sqlite
-- #{ alias
-- #  { init
-- #    { tables
CREATE TABLE IF NOT EXISTS Addresses(
  Username VARCHAR(16) NOT NULL,
  Address  VARCHAR(39) NOT NULL, -- Includes IPv4 and IPv6
  PRIMARY KEY(Username, Address)
);
-- # &
CREATE TABLE IF NOT EXISTS ClientRandomIds(
  Username       VARCHAR(16) NOT NULL,
  ClientRandomId INTEGER     NOT NULL,
  PRIMARY KEY(Username, ClientRandomId)
);
-- # &
CREATE TABLE IF NOT EXISTS DeviceIds(
  Username VARCHAR(16) NOT NULL,
  DeviceId VARCHAR(36) NOT NULL,
  PRIMARY KEY(Username, DeviceId)
);
-- # &
CREATE TABLE IF NOT EXISTS SelfSignedIds(
    Username     VARCHAR(16) NOT NULL,
    SelfSignedId VARCHAR(36) NOT NULL,
    PRIMARY KEY (Username, SelfSignedId)
);
-- # &
CREATE TABLE IF NOT EXISTS XUIDs(
  Username VARCHAR(16) PRIMARY KEY,
  XUID     VARCHAR(16) NOT NULL
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
-- #    { self_signed_id
-- #      :username string
-- #      :extraSelfSignedId ?string
SELECT DISTINCT Username
FROM SelfSignedIds
WHERE Username != LOWER(:username) AND (SelfSignedId IN (
  SELECT SelfSignedId
  FROM SelfSignedIds
  WHERE Username = LOWER(:username)
) OR SelfSignedId = :extraSelfSignedId);
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
-- #    { self_signed_id
-- #      :username string
-- #      :selfSignedId string
INSERT OR IGNORE INTO SelfSignedIds(Username, SelfSignedId)
VALUES(:username, :selfSignedId);
-- #    }
-- #    { xuid
-- #      :username string
-- #      :xuid string
INSERT OR IGNORE INTO XUIDs(Username, XUID)
VALUES(:username, :xuid);
-- #    }
-- #  }
-- #}