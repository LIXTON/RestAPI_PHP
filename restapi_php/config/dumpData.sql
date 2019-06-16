CREATE TABLE IF NOT EXISTS Contacts (
    contact_id int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
    first_name varchar(100) NOT NULL,
    sur_name varchar(100) NOT NULL,
    created datetime NOT NULL,
    modified timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY(contact_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS Emails (
    email varchar(100) NOT NULL,
    contact_id int(11) UNSIGNED NOT NULL,
    created datetime NOT NULL,
    modified timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY(email),
    CONSTRAINT FK_ContactEmail 
    FOREIGN KEY(contact_id) REFERENCES Contacts(contact_id)
    ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS PhoneNumbers (
    phone varchar(15) NOT NULL,
    contact_id int(11) UNSIGNED NOT NULL,
    created datetime NOT NULL,
    modified timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY(phone),
    CONSTRAINT FK_ContactPhone
    FOREIGN KEY(contact_id) REFERENCES Contacts(contact_id)
    ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;