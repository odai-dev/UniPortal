<?php
$page_title = 'Contact Us';
require_once 'config.php';
require_once 'db.php';

// Require login (before any HTML output)
requireLogin();

require_once 'header.php';

$success_message = '';
$error_message = '';

// Handle form submission
if ($_POST) {
    // Validate CSRF token
    $csrf_token = $_POST['csrf_token'] ?? '';
    if (!validateCSRFToken($csrf_token)) {
        $error_message = 'Invalid security token. Please try again.';
    } else {
        $subject = sanitizeInput($_POST['subject'] ?? '');
        $message = sanitizeInput($_POST['message'] ?? '');
        $captcha_input = sanitizeInput($_POST['captcha_code'] ?? '');
    
    // Validate CAPTCHA verification
    if (empty($captcha_input) || !isset($_SESSION['captcha_code']) || 
        strtoupper($captcha_input) !== $_SESSION['captcha_code']) {
        $error_message = 'Please enter the correct verification code from the image.';
    } elseif (empty($subject) || empty($message)) {
        $error_message = 'Please fill in all required fields.';
    } elseif (strlen($message) < 10) {
        $error_message = 'Message must be at least 10 characters long.';
    } else {
        try {
            $stmt = $pdo->prepare("INSERT INTO messages (user_id, subject, message) VALUES (?, ?, ?)");
            $stmt->execute([$_SESSION['user_id'], $subject, $message]);
            $success_message = 'Your message has been sent successfully! We will get back to you soon.';
            
            // Clear CAPTCHA session after successful submission
            unset($_SESSION['captcha_code']);
            
            // Clear form
            $_POST = [];
        } catch (PDOException $e) {
            $error_message = 'Database error occurred. Please try again.';
        }
    }
    }
}

// Get contact messages for admin
$messages = [];
if (isAdmin()) {
    try {
        $stmt = $pdo->prepare("
            SELECT m.*, u.name, u.email 
            FROM messages m 
            LEFT JOIN users u ON m.user_id = u.id 
            ORDER BY m.created_at DESC
        ");
        $stmt->execute();
        $messages = $stmt->fetchAll();
    } catch (PDOException $e) {
        $error_message = 'Error loading messages.';
    }
}
?>

<div class="row">
    <div class="col-12 col-lg-8">
        <h1><i class="fas fa-envelope me-2"></i>Contact Us</h1>
        
        <div class="card">
            <div class="card-header">
                <h5><i class="fas fa-paper-plane me-2"></i>Send us a Message</h5>
            </div>
            <div class="card-body">
                <?php if ($success_message): ?>
                    <div class="alert alert-success" role="alert">
                        <i class="fas fa-check-circle me-2"></i>
                        <?= $success_message ?>
                    </div>
                <?php endif; ?>
                
                <?php if ($error_message): ?>
                    <div class="alert alert-danger" role="alert">
                        <i class="fas fa-exclamation-circle me-2"></i>
                        <?= $error_message ?>
                    </div>
                <?php endif; ?>

                <form method="POST" action="">
                    <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                    <div class="mb-3">
                        <label for="subject" class="form-label">Subject <span class="text-danger">*</span></label>
                        <select class="form-select" id="subject" name="subject" required>
                            <option value="">Select a subject</option>
                            <option value="General Inquiry" <?= (isset($_POST['subject']) && $_POST['subject'] === 'General Inquiry') ? 'selected' : '' ?>>General Inquiry</option>
                            <option value="Technical Support" <?= (isset($_POST['subject']) && $_POST['subject'] === 'Technical Support') ? 'selected' : '' ?>>Technical Support</option>
                            <option value="Course Information" <?= (isset($_POST['subject']) && $_POST['subject'] === 'Course Information') ? 'selected' : '' ?>>Course Information</option>
                            <option value="Grade Inquiry" <?= (isset($_POST['subject']) && $_POST['subject'] === 'Grade Inquiry') ? 'selected' : '' ?>>Grade Inquiry</option>
                            <option value="Account Issues" <?= (isset($_POST['subject']) && $_POST['subject'] === 'Account Issues') ? 'selected' : '' ?>>Account Issues</option>
                            <option value="Feedback/Suggestions" <?= (isset($_POST['subject']) && $_POST['subject'] === 'Feedback/Suggestions') ? 'selected' : '' ?>>Feedback/Suggestions</option>
                            <option value="Other" <?= (isset($_POST['subject']) && $_POST['subject'] === 'Other') ? 'selected' : '' ?>>Other</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label for="message" class="form-label">Message <span class="text-danger">*</span></label>
                        <textarea class="form-control" id="message" name="message" rows="6" 
                                  placeholder="Please describe your inquiry in detail..." required><?= isset($_POST['message']) ? sanitizeInput($_POST['message']) : '' ?></textarea>
                        <small class="form-text text-muted">Minimum 10 characters required</small>
                    </div>

                    <!-- Image CAPTCHA Verification -->
                    <div class="mb-3">
                        <label for="captcha_code" class="form-label">Security Verification</label>
                        <div class="d-flex align-items-center">
                            <img src="captcha.php" alt="Security Code" id="captcha_image" class="border rounded me-3" style="cursor: pointer;" onclick="this.src='captcha.php?'+Math.random();">
                            <button type="button" class="btn btn-outline-secondary btn-sm me-3" onclick="document.getElementById('captcha_image').src='captcha.php?'+Math.random();">
                                <i class="fas fa-sync-alt"></i>
                            </button>
                            <div class="input-group flex-grow-1">
                                <span class="input-group-text">
                                    <i class="fas fa-shield-alt"></i>
                                </span>
                                <input type="text" class="form-control" id="captcha_code" name="captcha_code" 
                                       placeholder="Enter code from image" required maxlength="5">
                            </div>
                        </div>
                        <small class="form-text text-muted">Enter the 5-character code shown in the image</small>
                    </div>

                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-paper-plane me-2"></i>Send Message
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="col-12 col-lg-4">
        <div class="card">
            <div class="card-header">
                <h5><i class="fas fa-info-circle me-2"></i>Contact Information</h5>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <h6><i class="fas fa-university me-2 text-primary"></i>University Office</h6>
                    <p class="text-muted small mb-0">Main Campus, Administration Building</p>
                    <p class="text-muted small">Room 205, Second Floor</p>
                </div>

                <div class="mb-3">
                    <h6><i class="fas fa-clock me-2 text-primary"></i>Office Hours</h6>
                    <p class="text-muted small mb-0">Monday - Friday: 9:00 AM - 5:00 PM</p>
                    <p class="text-muted small mb-0">Saturday: 10:00 AM - 2:00 PM</p>
                    <p class="text-muted small">Sunday: Closed</p>
                </div>

                <div class="mb-3">
                    <h6><i class="fas fa-envelope me-2 text-primary"></i>Email Support</h6>
                    <p class="text-muted small mb-0">General: support@university.edu</p>
                    <p class="text-muted small mb-0">Technical: tech@university.edu</p>
                    <p class="text-muted small">Academic: academic@university.edu</p>
                </div>

                <div class="mb-3">
                    <h6><i class="fas fa-phone me-2 text-primary"></i>Phone Numbers</h6>
                    <p class="text-muted small mb-0">Main: (555) 123-4567</p>
                    <p class="text-muted small mb-0">Admissions: (555) 123-4568</p>
                    <p class="text-muted small">Emergency: (555) 123-4569</p>
                </div>

                <div class="alert alert-info">
                    <small>
                        <i class="fas fa-lightbulb me-1"></i>
                        <strong>Tip:</strong> For faster response times, please be specific about your inquiry and include relevant details.
                    </small>
                </div>
            </div>
        </div>

        <div class="card mt-3">
            <div class="card-header">
                <h5><i class="fas fa-question-circle me-2"></i>Frequently Asked</h5>
            </div>
            <div class="card-body">
                <div class="accordion" id="faqAccordion">
                    <div class="accordion-item">
                        <h2 class="accordion-header">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq1">
                                How do I reset my password?
                            </button>
                        </h2>
                        <div id="faq1" class="accordion-collapse collapse">
                            <div class="accordion-body">
                                <small>Go to your Profile page and use the "Change Password" section to update your password.</small>
                            </div>
                        </div>
                    </div>

                    <div class="accordion-item">
                        <h2 class="accordion-header">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq2">
                                How do I enroll in courses?
                            </button>
                        </h2>
                        <div id="faq2" class="accordion-collapse collapse">
                            <div class="accordion-body">
                                <small>Visit the Courses page, browse available courses, and click "View Details" then "Enroll" on courses you're interested in.</small>
                            </div>
                        </div>
                    </div>

                    <div class="accordion-item">
                        <h2 class="accordion-header">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq3">
                                Where can I view my grades?
                            </button>
                        </h2>
                        <div id="faq3" class="accordion-collapse collapse">
                            <div class="accordion-body">
                                <small>Click on "Grades" in the navigation menu to view all your course grades and academic performance.</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Admin Messages Section -->
<?php if (isAdmin() && !empty($messages)): ?>
<div class="row mt-5">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h5><i class="fas fa-inbox me-2"></i>Received Messages (Admin View)</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover datatable">
                        <thead>
                            <tr>
                                <th>From</th>
                                <th>Subject</th>
                                <th>Message Preview</th>
                                <th>Date</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($messages as $msg): ?>
                                <tr>
                                    <td>
                                        <div>
                                            <strong><?= sanitizeInput($msg['name'] ?? 'Unknown User') ?></strong>
                                            <br>
                                            <small class="text-muted"><?= sanitizeInput($msg['email'] ?? 'No email') ?></small>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="badge bg-primary"><?= sanitizeInput($msg['subject']) ?></span>
                                    </td>
                                    <td>
                                        <small><?= substr(sanitizeInput($msg['message']), 0, 100) ?>...</small>
                                    </td>
                                    <td>
                                        <small><?= date('M j, Y g:i A', strtotime($msg['created_at'])) ?></small>
                                    </td>
                                    <td>
                                        <button class="btn btn-sm btn-outline-primary" 
                                                onclick="viewMessage(<?= htmlspecialchars(json_encode($msg), ENT_QUOTES, 'UTF-8') ?>)">
                                            <i class="fas fa-eye"></i> View
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- View Message Modal -->
<div class="modal fade" id="viewMessageModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-envelope me-2"></i>Message Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <strong>From:</strong> <span id="msg-from"></span><br>
                    <strong>Email:</strong> <span id="msg-email"></span><br>
                    <strong>Subject:</strong> <span id="msg-subject" class="badge bg-primary"></span><br>
                    <strong>Date:</strong> <span id="msg-date"></span>
                </div>
                <hr>
                <div>
                    <strong>Message:</strong>
                    <div id="msg-content" class="mt-2 p-3 bg-light rounded"></div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<script>
function viewMessage(message) {
    document.getElementById('msg-from').textContent = message.name || 'Unknown User';
    document.getElementById('msg-email').textContent = message.email || 'No email';
    document.getElementById('msg-subject').textContent = message.subject;
    document.getElementById('msg-date').textContent = new Date(message.created_at).toLocaleString();
    document.getElementById('msg-content').textContent = message.message;
    
    new bootstrap.Modal(document.getElementById('viewMessageModal')).show();
}
</script>
<?php endif; ?>

<script src="form-enhancements.js"></script>

<script>
// Character counter for contact form message
document.addEventListener('DOMContentLoaded', function() {
    const messageField = document.getElementById('message');
    if (messageField) {
        messageField.addEventListener('input', function() {
            const length = this.value.length;
            const minLength = 10;
            
            if (length > 0 && length < minLength) {
                this.classList.add('is-invalid');
                this.classList.remove('is-valid');
            } else if (length >= minLength) {
                this.classList.add('is-valid');
                this.classList.remove('is-invalid');
            } else {
                this.classList.remove('is-valid', 'is-invalid');
            }
        });
    }
});
</script>

<?php require_once 'footer.php'; ?>