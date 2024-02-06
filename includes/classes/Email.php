<?php

namespace OTP;

class Email
{

    private $email_address;
    private $email_domain;
    private $allowed_email_domains;

    public function __construct($email_address)
    {
        $this->email_address = $email_address;
        $email = explode("@",  $email_address);
        $this->email_domain = $email[1];

        $this->allowed_email_domains = get_option('otp_allowed_email_domains');
    }

    public function is_valid()
    {
        foreach ($this->allowed_email_domains as $allowed_email_domain) {
            if (strcasecmp($allowed_email_domain, $this->email_domain) == 0) {
                return true;
            }
        }
        return false;
    }

    public function send($msg)
    {
        $email_subject_line = get_option('otp_email_subject_line', 'OTP');
        $email_replyto_address = get_option('otp_email_replyto_address', 'replyto@example.com');

        $curr_time = time();
        $headers = array(
            "From: " . $this->email_address,
            "Reply-To: " . $email_replyto_address
        );
        $resp = wp_mail($this->email_address, $email_subject_line  . ": " . $curr_time, $msg, $headers);
        return $resp;
    }
}
