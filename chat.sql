-- Create Users table
CREATE TABLE Users (
    user_id SERIAL PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    last_active TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT chk_email CHECK (email LIKE '%@%.%')
);

-- Create Groups table
CREATE TABLE Groups (
    group_id SERIAL PRIMARY KEY,
    group_name VARCHAR(100) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    created_by INT NOT NULL,
    FOREIGN KEY (created_by) REFERENCES Users(user_id) ON DELETE RESTRICT
);

-- Create GroupMembers table
CREATE TABLE GroupMembers (
    member_id SERIAL PRIMARY KEY,
    user_id INT NOT NULL,
    group_id INT NOT NULL,
    role TEXT DEFAULT 'member',
    joined_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES Users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (group_id) REFERENCES Groups(group_id) ON DELETE CASCADE,
    CONSTRAINT chk_role CHECK (role IN ('admin', 'member')),
    UNIQUE (user_id, group_id)
);

-- Create Messages table
CREATE TABLE Messages (
    message_id SERIAL PRIMARY KEY,
    sender_id INT NOT NULL,
    receiver_id INT,
    group_id INT,
    content TEXT NOT NULL,
    sent_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    is_group_message BOOLEAN DEFAULT FALSE,
    FOREIGN KEY (sender_id) REFERENCES Users(user_id) ON DELETE NO ACTION,
    FOREIGN KEY (receiver_id) REFERENCES Users(user_id) ON DELETE NO ACTION,
    FOREIGN KEY (group_id) REFERENCES Groups(group_id) ON DELETE CASCADE,
    CONSTRAINT chk_message_type CHECK (
        (is_group_message = TRUE AND group_id IS NOT NULL AND receiver_id IS NULL) OR
        (is_group_message = FALSE AND group_id IS NULL AND receiver_id IS NOT NULL)
    )
);

-- Create MessageStatus table
CREATE TABLE MessageStatus (
    status_id SERIAL PRIMARY KEY,
    message_id INT NOT NULL,
    user_id INT NOT NULL,
    status TEXT DEFAULT 'delivered',
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (message_id) REFERENCES Messages(message_id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES Users(user_id) ON DELETE CASCADE,
    CONSTRAINT chk_status CHECK (status IN ('delivered', 'read')),
    UNIQUE (message_id, user_id)
);

-- Indexes for better query performance
CREATE INDEX idx_messages_sender ON Messages(sender_id);
CREATE INDEX idx_messages_receiver ON Messages(receiver_id);
CREATE INDEX idx_messages_group ON Messages(group_id);
CREATE INDEX idx_message_status ON MessageStatus(message_id, user_id);
