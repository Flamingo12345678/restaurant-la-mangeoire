-- Active: 1737405433981@@localhost@3306
-- Table Clients
CREATE TABLE Clients (
    ClientID INT IDENTITY(1,1) PRIMARY KEY,
    Nom NVARCHAR(100) NOT NULL,
    Prenom NVARCHAR(100) NOT NULL,
    Email NVARCHAR(100) NOT NULL,
    Telephone NVARCHAR(20)
);

-- Table Tables
CREATE TABLE Tables (
    TableID INT IDENTITY(1,1) PRIMARY KEY,
    NumeroTable INT NOT NULL,
    Capacite INT NOT NULL
);

-- Table R�servations
CREATE TABLE Reservations (
    ReservationID INT IDENTITY(1,1) PRIMARY KEY,
    ClientID INT,
    TableID INT,
    DateReservation DATE NOT NULL,
    HeureReservation TIME NOT NULL,
    NombrePersonnes INT NOT NULL,
    FOREIGN KEY (ClientID) REFERENCES Clients(ClientID),
    FOREIGN KEY (TableID) REFERENCES Tables(TableID)
);

-- Table Menus
CREATE TABLE Menus (
    MenuID INT IDENTITY(1,1) PRIMARY KEY,
    NomItem NVARCHAR(100) NOT NULL,
    Description NVARCHAR(MAX),
    Prix DECIMAL(10, 2) NOT NULL
);

-- Table Commandes
CREATE TABLE Commandes (
    CommandeID INT IDENTITY(1,1) PRIMARY KEY,
    ReservationID INT,
    MenuID INT,
    Quantite INT NOT NULL,
    FOREIGN KEY (ReservationID) REFERENCES Reservations(ReservationID),
    FOREIGN KEY (MenuID) REFERENCES Menus(MenuID)
);

-- Table Employ�s
CREATE TABLE Employes (
    EmployeID INT IDENTITY(1,1) PRIMARY KEY,
    Nom NVARCHAR(100) NOT NULL,
    Prenom NVARCHAR(100) NOT NULL,
    Poste NVARCHAR(50) NOT NULL,
    Salaire DECIMAL(10, 2) NOT NULL,
    DateEmbauche DATE NOT NULL
);

-- Table Paiements
CREATE TABLE Paiements (
    PaiementID INT IDENTITY(1,1) PRIMARY KEY,
    ReservationID INT,
    Montant DECIMAL(10, 2) NOT NULL,
    DatePaiement DATE NOT NULL,
    ModePaiement NVARCHAR(50),
    FOREIGN KEY (ReservationID) REFERENCES Reservations(ReservationID)
);