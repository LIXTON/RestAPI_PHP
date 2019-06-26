CREATE TABLE IF NOT EXISTS Contacts (
    contact_id int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
    first_name varchar(100) NOT NULL,
    sur_name varchar(100) NOT NULL,
    PRIMARY KEY(contact_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS Emails (
    email varchar(100) NOT NULL,
    contact_id int(11) UNSIGNED NOT NULL,
    PRIMARY KEY(email),
    CONSTRAINT FK_ContactEmail 
    FOREIGN KEY(contact_id) REFERENCES Contacts(contact_id)
    ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS PhoneNumbers (
    phone varchar(15) NOT NULL,
    contact_id int(11) UNSIGNED NOT NULL,
    PRIMARY KEY(phone),
    CONSTRAINT FK_ContactPhone
    FOREIGN KEY(contact_id) REFERENCES Contacts(contact_id)
    ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

INSERT INTO Contacts (first_name, sur_name)
VALUES ('Merengue', 'Perez');
INSERT INTO Contacts (first_name, sur_name)
VALUES ('Juancho', 'Garcia');
INSERT INTO Contacts (first_name, sur_name)
VALUES ('Oscar', 'Sanchez');
INSERT INTO Contacts (first_name, sur_name)
VALUES ('Ignacio', 'Garcia');
INSERT INTO Contacts (first_name, sur_name)
VALUES ('Joaquin', 'Perez');
INSERT INTO Contacts (first_name, sur_name)
VALUES ('Juanito', 'Garcia');

INSERT INTO Emails (contact_id, email)
VALUES (1, "test@gmail.com");
INSERT INTO Emails (contact_id, email)
VALUES (1, "dummy@gmail.com");
INSERT INTO Emails (contact_id, email)
VALUES (1, "test@hotmail.com");
INSERT INTO Emails (contact_id, email)
VALUES (2, "asdasd@red.com");
INSERT INTO Emails (contact_id, email)
VALUES (3, "hhh@red.com");
INSERT INTO Emails (contact_id, email)
VALUES (3, "hhh@hotmail.com");
INSERT INTO Emails (contact_id, email)
VALUES (4, "rediant@hint.com");
INSERT INTO Emails (contact_id, email)
VALUES (4, "dummy@fail.com");
INSERT INTO Emails (contact_id, email)
VALUES (4, "account@test.com");
INSERT INTO Emails (contact_id, email)
VALUES (5, "hhh@gmail.com");
INSERT INTO Emails (contact_id, email)
VALUES (6, "test@red.com");

INSERT INTO PhoneNumbers (contact_id, phone)
VALUES (1, "6856244444");
INSERT INTO PhoneNumbers (contact_id, phone)
VALUES (1, "6851231234");
INSERT INTO PhoneNumbers (contact_id, phone)
VALUES (2, "6566789321");
INSERT INTO PhoneNumbers (contact_id, phone)
VALUES (2, "6561471212");
INSERT INTO PhoneNumbers (contact_id, phone)
VALUES (3, "6247894545");
INSERT INTO PhoneNumbers (contact_id, phone)
VALUES (3, "6244589696");
INSERT INTO PhoneNumbers (contact_id, phone)
VALUES (4, "3321478989");
INSERT INTO PhoneNumbers (contact_id, phone)
VALUES (5, "9154141336");
INSERT INTO PhoneNumbers (contact_id, phone)
VALUES (5, "9154787966");
INSERT INTO PhoneNumbers (contact_id, phone)
VALUES (5, "8704568282");
INSERT INTO PhoneNumbers (contact_id, phone)
VALUES (6, "332854696");
