-- Update Commandes table to add DatePaiement field
ALTER TABLE Commandes 
ADD COLUMN DatePaiement DATETIME NULL;

-- Create Paiements table if it doesn't exist
CREATE TABLE IF NOT EXISTS Paiements (
    PaiementID INT AUTO_INCREMENT PRIMARY KEY,
    CommandeID INT,
    ReservationID INT NULL,
    Montant DECIMAL(10,2) NOT NULL,
    MethodePaiement VARCHAR(50) NOT NULL,
    NumeroTransaction VARCHAR(100),
    DatePaiement DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (CommandeID) REFERENCES Commandes (CommandeID) ON DELETE CASCADE,
    FOREIGN KEY (ReservationID) REFERENCES Reservations (ReservationID) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
