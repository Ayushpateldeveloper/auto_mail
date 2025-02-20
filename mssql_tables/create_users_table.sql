CREATE TABLE users (
    id INT PRIMARY KEY IDENTITY(1,1),  -- Auto-incrementing ID
    username VARCHAR(255) NOT NULL,    -- Username field
    email VARCHAR(255) NOT NULL UNIQUE, -- Email field with unique constraint
    created_at DATETIME DEFAULT GETDATE() -- Timestamp for record creation
);