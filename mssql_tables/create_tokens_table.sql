CREATE TABLE tokens (
    id INT PRIMARY KEY IDENTITY(1,1),
    access_token VARCHAR(255) NOT NULL,
    created_at DATETIME DEFAULT GETDATE()
);
