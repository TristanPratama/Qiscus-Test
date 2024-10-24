<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chat Display</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome untuk ikon -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <!-- Custom CSS -->
    <style>
        body {
            background-color: #f8f9fa;
        }
        
        .chat-container {
            max-width: 1200px;
            margin: 20px auto;
        }

        .rooms-list {
            max-height: 400px;
            overflow-y: auto;
        }

        .room-item {
            cursor: pointer;
            transition: background-color 0.3s;
        }

        .room-item:hover {
            background-color: #f1f3f5;
        }

        .room-item.active {
            background-color: #e9ecef;
        }

        .chat-messages {
            height: 480px;
            overflow-y: auto;
            padding: 20px;
            display: flex;
            flex-direction: column;
        }

        .message {
            display: flex;
            flex-direction: column;
            margin-bottom: 20px;
            max-width: 80%;
            word-wrap: break-word;
        }

        .admin-message {
            align-self: flex-end;
        }

        .admin-message .message-content {
            background-color: #81c784;
            color: white;
            border-radius: 15px;
            border-bottom-right-radius: 5px;
            padding: 12px;
        }

        .agent-message {
            align-self: flex-end;
        }

        .agent-message .message-content {
            background-color: #64b5f6;
            color: white;
            border-radius: 15px;
            border-bottom-right-radius: 5px;
            padding: 12px;
        }

        .customer-message {
            align-self: flex-start;
        }

        .customer-message .message-content {
            background-color: #f8f9fa; 
            border: 1px solid #dee2e6;
            border-radius: 15px;
            border-bottom-left-radius: 5px;
            padding: 12px;
        }

        .badge {
            font-size: 0.8em;
            padding: 0.25em 0.6em;
            border-radius: 0.25rem;
        }

        .bg-success { background-color: #81c784; } 
        .bg-primary { background-color: #64b5f6; }
        .bg-secondary { background-color: #6c757d; }


        .sender-name {
            font-size: 0.8em;
            margin-bottom: 5px;
            opacity: 0.8;
        }

        .timestamp {
            font-size: 0.7em;
            margin-top: 5px;
            opacity: 0.7;
        }

        .agent-message .sender-name, .agent-message .timestamp {
            text-align: right;
            color: #6c757d;
        }

        .customer-message .sender-name, .customer-message .timestamp {
            text-align: left;
            color: #6c757d;
        }

        .participant-list {
            max-height: 245px;
            overflow-y: auto;
        }

        .shared-files-list {
            max-height: 220px;
            overflow-y: auto;
        }

        @media (max-width: 768px) {
            #roomsList {
                position: fixed;
                top: 0;
                left: 0;
                right: 0;
                bottom: 0;
                z-index: 1050;
                background: rgba(0, 0, 0, 0.5);
                padding: 1rem;
            }

            #roomsList .card {
                margin: 0;
            }

            #roomsList .card-body {
                height: calc(100% - 56px);
                overflow-y: auto;
            }

            .room-toggle-btn {
                position: fixed;
                bottom: 20px;
                right: 20px;
                z-index: 1001;
                border-radius: 50%;
                width: 50px;
                height: 50px;
                padding: 0;
                display: flex;
                align-items: center;
                justify-content: center;
                box-shadow: 0 2px 5px rgba(0, 0, 0, 0.2);
            }

            .chat-container {
                margin: 20px auto;
            }

            iframe {
                width : 95%;
            }
        }

        @media (min-width: 768px) and (max-width: 991px) {
            .chat-messages {
                height: 450px;
            }

            .shared-files-list {
                max-height: 200px; 
            }

            iframe {
                width : 98%;
            }
        }
    </style>
</head>
<body>
<?php

// Membaca dan decode file JSON
$jsonData = file_get_contents('enhanced_chat.json');
$data = json_decode($jsonData, true);

// Function untuk mendapatkan nama participant berdasarkan email
function getParticipantName($email, $participants) {
    foreach ($participants as $participant) {
        if ($participant['id'] === $email) {
            return $participant['name'];
        }
    }
    return $email;
}

// Function untuk mendapatkan role berdasarkan email
function getParticipantRole($email, $participants) {
    foreach ($participants as $participant) {
        if ($participant['id'] === $email) {
            return $participant['role'];
        }
    }
    return null;
}

// Function untuk format timestamp
function formatTimestamp($timestamp) {
    $date = new DateTime($timestamp);
    return $date->format('H:i, d M Y');
}

// Function untuk format file size
function formatFileSize($bytes) {
    if ($bytes >= 1073741824) {
        return number_format($bytes / 1073741824, 2) . ' GB';
    } elseif ($bytes >= 1048576) {
        return number_format($bytes / 1048576, 2) . ' MB';
    } elseif ($bytes >= 1024) {
        return number_format($bytes / 1024, 2) . ' KB';
    }
    return $bytes . ' bytes';
}

// Function untuk render media content
function renderMediaContent($comment) {
    switch ($comment['type']) {
        case 'image':
            return sprintf(
                '<div class="media-preview">
                    <a href="%s" target="_blank">
                        <img src="%s" alt="%s" class="img-fluid">
                    </a>
                </div>',
                htmlspecialchars($comment['file_url']),
                htmlspecialchars($comment['thumbnail_url'] ?? $comment['file_url']),
                htmlspecialchars($comment['message'])
            );
        
        case 'video':
            return sprintf(
                '<div class="media-preview">
                    <iframe src="%s frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen">
                    </iframe>
                </div>',
                htmlspecialchars($comment['file_url'])
            );
        
        case 'document':
            return sprintf(
                '<div class="document-preview">
                    <i class="far fa-file-pdf"></i>
                    %s
                    <div class="small text-muted">%s</div>
                    <a href="%s" class="btn btn-sm btn-primary mt-2" target="_blank">
                        <i class="fas fa-download"></i> Download
                    </a>
                </div>',
                htmlspecialchars($comment['message']),
                formatFileSize($comment['file_size']),
                htmlspecialchars($comment['file_url'])
            );
        
        default:
            return htmlspecialchars($comment['message']);
    }
}

// Get current room ID from query parameter or use first room
$currentRoomId = $_GET['room_id'] ?? $data['results'][0]['room']['id'];
?>

<div class="container-fluid chat-container">
    <div class="row">
        <!-- Toggle Button untuk Mobile -->
        <button class="btn btn-primary room-toggle-btn d-md-none justify-center" type="button" data-bs-toggle="collapse" data-bs-target="#roomsList" aria-expanded="false" aria-controls="roomsList">
            <i class="fas fa-users"></i>
        </button>

        <!-- Rooms List dengan Collapse -->
        <div class="col-12 col-md-3 collapse d-md-block" id="roomsList">
            <div class="card shadow-sm">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">Chat Rooms</h5>
                    <button class="btn btn-sm btn-link d-md-none" type="button" data-bs-toggle="collapse" data-bs-target="#roomsList">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <div class="card-body p-0 rooms-list">
                    <?php foreach ($data['results'] as $result): ?>
                        <a href="?room_id=<?php echo $result['room']['id']; ?>" 
                           class="room-item d-flex align-items-center p-3 text-decoration-none text-dark
                                  <?php echo $result['room']['id'] == $currentRoomId ? 'active' : ''; ?>">
                            <img src="<?php echo htmlspecialchars($result['room']['image_url']); ?>" 
                                 class="rounded-circle me-3"
                                 width="40"
                                 height="40"
                                 alt="<?php echo htmlspecialchars($result['room']['name']); ?>"
                                 onerror="this.src='https://via.placeholder.com/40'">
                            <div>
                                <h6 class="mb-1"><?php echo htmlspecialchars($result['room']['name']); ?></h6>
                                <small class="text-muted">
                                    <?php echo count($result['room']['participant']) - 1; ?> participants
                                </small>
                            </div>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <!-- Chat Area -->
        <?php
        // Find current room data
        $currentRoom = null;
        foreach ($data['results'] as $result) {
            if ($result['room']['id'] == $currentRoomId) {
                $currentRoom = $result;
                break;
            }
        }
        
        if ($currentRoom):
        ?>
        <div class="col-md-6 mb-4">
            <div class="card shadow-sm">
                <!-- Room Header -->
                <div class="card-header">
                    <div class="d-flex align-items-center">
                        <img src="<?php echo htmlspecialchars($currentRoom['room']['image_url']); ?>" 
                             class="rounded-circle me-3"
                             width="40"
                             height="40"
                             alt="<?php echo htmlspecialchars($currentRoom['room']['name']); ?>"
                             onerror="this.src='https://via.placeholder.com/40'">
                        <div>
                            <h5 class="mb-1"><?php echo htmlspecialchars($currentRoom['room']['name']); ?></h5>
                            <small class="text-muted">
                                ID: <?php echo htmlspecialchars($currentRoom['room']['id']); ?>
                            </small>
                        </div>
                    </div>
                </div>

                <!-- Chat Messages -->
                <div class="card-body chat-messages">
                    <?php foreach ($currentRoom['comments'] as $comment): 
                        // Dapatkan role dari pengirim pesan
                        $role = getParticipantRole($comment['sender'], $currentRoom['room']['participant']);
                        
                        // Tentukan apakah pengirim adalah customer, agent, atau admin berdasarkan role
                        $isCustomer = $role === 2;
                        $isAgent = $role === 1;
                        $isAdmin = $role === 0;

                        $senderName = getParticipantName($comment['sender'], $currentRoom['room']['participant']);
                    ?>
                        <div class="message <?php 
                            echo $isCustomer ? 'customer-message' : ($isAgent ? 'agent-message' : 'admin-message'); 
                        ?>">
                            <div class="sender-name">
                                <?php echo htmlspecialchars($senderName); ?>
                            </div>
                            <div class="message-content">
                                <?php echo renderMediaContent($comment); ?>
                            </div>
                            <div class="timestamp">
                                <?php echo formatTimestamp($comment['created_at']); ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <!-- Chat Input Area -->
                <div class="card-footer">
                    <form class="d-flex gap-2" id="chatForm">
                        <div class="input-group">
                            <input type="text" class="form-control" placeholder="Type your message...">
                            <button type="button" class="btn btn-outline-secondary" id="attachButton">
                                <i class="fas fa-paperclip"></i>
                            </button>
                            <input type="file" id="fileInput" class="d-none" multiple 
                                   accept="image/*,video/*,application/pdf">
                        </div>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-paper-plane"></i>
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Participants Sidebar -->
        <div class="col-md-3">
            <div class="card shadow-sm">
                <div class="card-header">
                    <h5 class="card-title mb-0">Participants</h5>
                </div>
                <div class="card-body p-0 participant-list">
                    <?php foreach ($currentRoom['room']['participant'] as $participant): ?>
                        <div class="d-flex align-items-center p-3 border-bottom">
                            <img src="<?php echo htmlspecialchars($participant['avatar_url']); ?>" 
                                 class="rounded-circle me-3"
                                 width="40"
                                 height="40"
                                 alt="<?php echo htmlspecialchars($participant['name']); ?>"
                                 onerror="this.src='https://via.placeholder.com/40'">
                            <div>
                                <h6 class="mb-1"><?php echo htmlspecialchars($participant['name']); ?></h6>
                                <span class="badge bg-<?php 
                                        echo match($participant['role']) {
                                            0 => 'success', 
                                            1 => 'primary',
                                            2 => 'secondary'
                                        };
                                    ?>">
                                    <?php 
                                    echo match($participant['role']) {
                                        0 => 'Admin',
                                        1 => 'Agent',
                                        2 => 'Customer',
                                    };
                                    ?>
                                </span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- File Preview Area -->
            <div class="card shadow-sm mt-3">
                <div class="card-header">
                    <h5 class="card-title mb-0">Shared Files</h5>
                </div>
                <div class="card-body">
                    <div class="list-group list-group-flush shared-files-list">
                        <?php 
                        foreach ($currentRoom['comments'] as $comment):
                            if (in_array($comment['type'], ['image', 'video', 'document'])):
                        ?>
                            <a href="<?php echo htmlspecialchars($comment['file_url']); ?>" 
                               class="list-group-item list-group-item-action" 
                               target="_blank">
                                <div class="d-flex w-100 justify-content-between">
                                    <h6 class="mb-1">
                                        <i class="fas fa-<?php 
                                            echo match($comment['type']) {
                                                'image' => 'image',
                                                'video' => 'video',
                                                'document' => 'file-pdf',
                                                default => 'file'
                                            };
                                        ?>"></i>
                                        <?php echo htmlspecialchars($comment['message']); ?>
                                    </h6>
                                </div>
                                <small class="text-muted">
                                    <?php echo formatFileSize($comment['file_size']); ?> • 
                                    <?php echo formatTimestamp($comment['created_at']); ?>
                                </small>
                            </a>
                        <?php 
                            endif;
                        endforeach; 
                        ?>
                    </div>
                </div>
            </div>
        </div>
        <?php else: ?>
        <div class="col-md-9">
            <div class="card shadow-sm">
                <div class="card-body text-center py-5">
                    <h4>Room tidak ditemukan</h4>
                    <p class="text-muted">Silakan pilih room chat dari daftar di sebelah kiri.</p>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- Bootstrap JS and Popper.js -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<!-- Custom JavaScript -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Fungsi untuk menutup rooms list setelah memilih room pada mobile
    if (window.innerWidth < 768) {
        const roomItems = document.querySelectorAll('.room-item');
        const roomsList = document.getElementById('roomsList');
        
        roomItems.forEach(item => {
            item.addEventListener('click', () => {
                const bsCollapse = bootstrap.Collapse.getInstance(roomsList);
                if (bsCollapse) {
                    bsCollapse.hide();
                }
            });
        });

        // Menutup rooms list ketika mengklik area overlay
        roomsList.addEventListener('click', (e) => {
            if (e.target === roomsList) {
                const bsCollapse = bootstrap.Collapse.getInstance(roomsList);
                if (bsCollapse) {
                    bsCollapse.hide();
                }
            }
        });
    }

    // Attachment button handler
    const attachButton = document.getElementById('attachButton');
    const fileInput = document.getElementById('fileInput');
    
    attachButton.addEventListener('click', () => {
        fileInput.click();
    });

    // File input change handler
    fileInput.addEventListener('change', (e) => {
        const files = Array.from(e.target.files);
        files.forEach(file => {
            console.log('File selected:', file.name, file.type, file.size);
        });
    });

    // Scroll ke bawah chat
    const chatMessages = document.querySelector('.chat-messages');
    chatMessages.scrollTop = chatMessages.scrollHeight;

    // Form submit handler
    const chatForm = document.getElementById('chatForm');
    chatForm.addEventListener('submit', (e) => {
        e.preventDefault();
        const messageInput = chatForm.querySelector('input[type="text"]');
        const message = messageInput.value.trim();
        
        if (message) {
            console.log('Message sent:', message);
            messageInput.value = '';
        }
    });
});
</script>
</body>
</html>