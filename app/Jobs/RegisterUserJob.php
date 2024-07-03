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

            // 4. Solve the reCAPTCHA 
            $siteKey = 'YOUR_RECAPTCHA_SITE_KEY'; // Replace with your reCAPTCHA site key
            $pageUrl = 'https://yoursiteurl/registration-page'; // Replace with your registration page URL
            $captchaResponse = $this->solveRecaptcha($csrf_token, $siteKey, $pageUrl);
            // 5. Further processing after solving CAPTCHA

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
     * Solve the reCAPTCHA using 2Captcha service.
     *
     * @param string $csrfToken
     * @param string $siteKey
     * @param string $pageUrl
     * @return string|null Captcha response or null on failure
     * @throws Exception
     */
    protected function solveRecaptcha(string $csrfToken, string $siteKey, string $pageUrl): ?string
    {
        $client = new Client();
        $apiKey = 'YOUR_2CAPTCHA_API_KEY'; // Replace with your 2Captcha API key

        try {
            // 1. Request solving captcha from 2Captcha API
            $response = $client->post('http://2captcha.com/in.php', [
                'form_params' => [
                    'key' => $apiKey,
                    'method' => 'userrecaptcha',
                    'googlekey' => $siteKey,
                    'pageurl' => $pageUrl,
                    'json' => 1, // JSON response format
                ]
            ]);

            $responseData = json_decode($response->getBody(), true);

            if ($responseData['status'] == 0) {
                throw new Exception('2Captcha API error: ' . $responseData['request']);
            }

            $captchaId = $responseData['request'];

            // 2. Polling 2Captcha for the response
            $maxAttempts = 10;
            $pollInterval = 5; // seconds
            $attempts = 0;
            $captchaResponse = '';

            do {
                sleep($pollInterval);
                $response = $client->get('http://2captcha.com/res.php', [
                    'query' => [
                        'key' => $apiKey,
                        'action' => 'get',
                        'id' => $captchaId,
                        'json' => 1, // JSON response format
                    ]
                ]);

                $responseData = json_decode($response->getBody(), true);

                if ($responseData['status'] == 1) {
                    $captchaResponse = $responseData['request'];
                    break;
                }

                $attempts++;
            } while ($attempts < $maxAttempts);

            if (empty($captchaResponse)) {
                throw new Exception('Failed to solve CAPTCHA within the maximum attempts.');
            }

            return $captchaResponse;
        } catch (RequestException $e) {
            Log::error('CAPTCHA solving request failed: ' . $e->getMessage());
            throw new Exception('CAPTCHA solving request failed: ' . $e->getMessage());
        } catch (Exception $e) {
            Log::error('CAPTCHA solving error: ' . $e->getMessage());
            throw new Exception('CAPTCHA solving error: ' . $e->getMessage());
        }
    }
}
