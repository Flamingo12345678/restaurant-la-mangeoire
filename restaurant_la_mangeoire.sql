-- Création de la base de données
CREATE DATABASE IF NOT EXISTS restaurant_la_mangeoire
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE restaurant_la_mangeoire;

-- Table Clients
CREATE TABLE IF NOT EXISTS Clients (
    ClientID INT AUTO_INCREMENT PRIMARY KEY,
    Nom VARCHAR(100) NOT NULL,
    Prenom VARCHAR(100) NOT NULL,
    Email VARCHAR(100) NOT NULL UNIQUE,
    Telephone VARCHAR(20)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table TablesRestaurant
CREATE TABLE IF NOT EXISTS TablesRestaurant (
    TableID INT AUTO_INCREMENT PRIMARY KEY,
    NumeroTable INT NOT NULL UNIQUE,
    Capacite INT NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table Reservations (adaptée pour réservation publique)
CREATE TABLE IF NOT EXISTS Reservations (
    ReservationID INT AUTO_INCREMENT PRIMARY KEY,
    ClientID INT,
    TableID INT,
    DateReservation DATETIME NOT NULL,
    Statut ENUM('Réservée', 'Annulée') DEFAULT 'Réservée',
    nom_client VARCHAR(100),
    email_client VARCHAR(100),
    nb_personnes INT,
    telephone VARCHAR(30),
    FOREIGN KEY (ClientID) REFERENCES Clients(ClientID) ON DELETE CASCADE,
    FOREIGN KEY (TableID) REFERENCES TablesRestaurant(TableID) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table Menus
CREATE TABLE IF NOT EXISTS Menus (
    MenuID INT AUTO_INCREMENT PRIMARY KEY,
    NomItem VARCHAR(100) NOT NULL,
    Description TEXT,
    Prix DECIMAL(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table Commandes
CREATE TABLE IF NOT EXISTS Commandes (
    CommandeID INT AUTO_INCREMENT PRIMARY KEY,
    ReservationID INT NOT NULL,
    MenuID INT NOT NULL,
    Quantite INT NOT NULL,
    FOREIGN KEY (ReservationID) REFERENCES Reservations(ReservationID) ON DELETE CASCADE,
    FOREIGN KEY (MenuID) REFERENCES Menus(MenuID) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table Paiements
CREATE TABLE IF NOT EXISTS Paiements (
    PaiementID INT AUTO_INCREMENT PRIMARY KEY,
    ReservationID INT NOT NULL,
    Montant DECIMAL(10,2) NOT NULL,
    DatePaiement DATETIME NOT NULL,
    ModePaiement VARCHAR(50),
    FOREIGN KEY (ReservationID) REFERENCES Reservations(ReservationID) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Index pour accélérer les recherches
CREATE INDEX IDX_Clients_Email ON Clients(Email);
CREATE INDEX IDX_Tables_NumeroTable ON TablesRestaurant(NumeroTable);
CREATE INDEX IDX_Reservations_Date ON Reservations(DateReservation);