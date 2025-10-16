<?php
/**
 * Contact Service - Handles contact form submissions
 */

class ContactService {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    /**
     * Submit contact form
     */
    public function submitContact($data) {
        // Validate required fields
        if (empty($data['fullName']) || empty($data['email']) || empty($data['message'])) {
            throw new Exception("Name, email, and message are required");
        }
        
        // Validate email
        if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            throw new Exception("Invalid email address");
        }
        
        // Check for spam (simple honeypot)
        if (!empty($data['website'])) {
            throw new Exception("Spam detected");
        }
        
        // Insert submission
        $query = "
            INSERT INTO contact_submissions (
                name, email, phone, project_type, budget_range, timeline, 
                message, newsletter, ip_address, user_agent
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ";
        
        $stmt = $this->db->prepare($query);
        $result = $stmt->execute([
            $data['fullName'],
            $data['email'],
            $data['phone'] ?? '',
            $data['projectType'] ?? '',
            $data['budgetRange'] ?? '',
            $data['timeline'] ?? '',
            $data['message'],
            isset($data['newsletter']) ? 1 : 0,
            $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
        ]);
        
        if ($result) {
            // Send notification email (in production)
            $this->sendNotificationEmail($data);
            
            // Log activity
            $this->logActivity('contact_submission', "New contact message from: {$data['fullName']}");
        }
        
        return $result;
    }
    
    /**
     * Send notification email
     */
    private function sendNotificationEmail($data) {
        // In production, implement actual email sending
        // This is a placeholder for email functionality
        $to = 'contact@imranshiundu.eu';
        $subject = 'New Contact Form Submission';
        $message = "
            Name: {$data['fullName']}
            Email: {$data['email']}
            Phone: {$data['phone'] ?? 'Not provided'}
            Project Type: {$data['projectType'] ?? 'Not specified'}
            Budget: {$data['budgetRange'] ?? 'Not specified'}
            Timeline: {$data['timeline'] ?? 'Not specified'}
            
            Message:
            {$data['message']}
        ";
        
        // mail($to, $subject, $message);
        error_log("Contact form submission: " . $message);
    }
    
    /**
     * Log activity
     */
    private function logActivity($type, $description) {
        $adminId = $_SESSION['admin_id'] ?? null;
        $ipAddress = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
        
        $stmt = $this->db->prepare("
            INSERT INTO admin_activity_log (admin_id, activity_type, description, ip_address, user_agent) 
            VALUES (?, ?, ?, ?, ?)
        ");
        $stmt->execute([$adminId, $type, $description, $ipAddress, $userAgent]);
    }
}
?>