<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Support\Facades\Log;
use Exception;
use GuzzleHttp\Client;
use PhpImap\Mailbox;

class RegisterUserJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $email;
    protected $fullName;
    protected $password;

    /**
     * Create a new job instance.
     *
     * @param string $email
     * @param string $fullName
     * @param string $password
     * @return void
     */
    public function __construct(string $email, string $fullName, string $password)
    {
        $this->email = $email;
        $this->fullName = $fullName;
        $this->password = $password;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $client = new Client();

        try {
            // 1. Visit the registration page and get the CSRF token
            $response = $client->get('https://yoursiteurl/register.php');
            $csrf_token = $this->extractCsrfToken($response->getBody());

            // 2. Submit the registration form
            $response = $client->post('https://yoursiteurl/verify.php', [
                'form_params' => [
                    'stoken' => $csrf_token,
                    'fullname' => $this->fullName,
                    'email' => $this->email,
                    'password' => $this->password,
                    'email_signature' => base64_encode($this->email),
                ]
            ]);

            // Log response or handle it as needed
            Log::info('Registration Form Submitted', ['response' => $response->getBody()]);

            // 3. Verify the email
            $this->verifyEmail();

            // 4. Solve the reCAPTCHA (implement this logic)

        } catch (RequestException $e) {
            Log::error('Registration automation error: ' . $e->getMessage());
        } catch (Exception $e) {
            Log::error('Unexpected error during registration: ' . $e->getMessage());
        }
    }

    /**
     * Extract the CSRF token from the HTML response.
     *
     * @param string $html
     * @return string
     */
    protected function extractCsrfToken(string $html): string
    {
        $pattern = '/<input type="hidden" name="stoken" value="(.*?)">/';
        preg_match($pattern, $html, $matches);
        return isset($matches[1]) ? $matches[1] : '';
    }

    /**
     * Verify the email address.
     *
     * @return void
     */
    protected function verifyEmail()
    {
        $this->getLatestOtp();
    }

    /**
     * Fetch the latest OTP from the email.
     *
     * @return string|null
     * @throws Exception
     */
    private function getLatestOtp(): ?string
    {
        try {
            $mailbox = new Mailbox(
                '{imap.gmail.com:993/imap/ssl}INBOX',
                env('EMAIL_USERNAME'),
                env('EMAIL_PASSWORD'),
                __DIR__,
                'UTF-8'
            );

            $mailsIds = $mailbox->searchMailbox('ALL');
            if (!$mailsIds) {
                throw new Exception('Mailbox is empty');
            }

            $latestMail = $mailbox->getMail(max($mailsIds));

            return $this->extractOtp($latestMail->textPlain);
        } catch (Exception $e) {
            throw new Exception('Error fetching OTP: ' . $e->getMessage());
        }
    }

    /**
     * Extract OTP from email body.
     *
     * @param string $emailBody
     * @return string|null
     */
    private function extractOtp(string $emailBody): ?string
    {
        if (preg_match('/\b\d{6}\b/', $emailBody, $matches)) {
            return $matches[0];
        }
        return null;
    }


    /**
     * Solve the reCAPTCHA.
     *
     * @return void
     */
    protected function solveRecaptcha()
    {
        // Implement reCAPTCHA solving logic here
        // This could involve using a third-party service or API to solve the reCAPTCHA
    }
}
