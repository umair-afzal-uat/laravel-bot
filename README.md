# Developers Challenge!

# Table of Contents

-   [Introduction](#introduction)
-   [Challenge Overview And Limitations](#challenge-overview-and-limitations)
-   [Tech Stack](#tech-stack)
-   [Prerequisites](#prerequisites)
-   [Installation](#installation)
-   [Contributing](#contributing)
-   [Best Practices](#best-practices)
-   [License](#license)

## Introduction

Welcome to the Developers Challenge! This challenge tests your PHP (Laravel framework) skills in automating the registration process on the specified website using a bot.

# Challenge Overview And Limitations

The objective is to develop a PHP script that autonomously completes the following steps:

1. **Initiate Registration**: Start the registration process on the specified website.
2. **Submit Registration Form**: Fill out and submit the registration form with unique data for each execution.
3. **Verify Email Address**: Retrieve and verify the email address using the verification code sent to the provided email [It currently only supports one user email at a time. To add multiple emails, each requires its respective app key for parsing from the backend. If you can provide an array of emails along with their app keys, it would enable seamless processing of multiple emails.].
4. **Complete ReCaptcha**: Successfully complete the ReCaptcha challenge to finalize the registration.

## Tech Stack

-   Backend: Laravel
-   Database: Sqlite

## Prerequisites

-   Laravel: 11.x

## Installation

1. Clone the repository: `https://github.com/umair-afzal-uat/laravel-bot.git`
2. Install backend dependencies: `composer install` in the root folder.
3. Start the backend server: `php artisan serve`
4. For docker: docker-compose up -d --build

## Contributing

Contributions are welcome! Feel free to submit pull requests or open issues.

# Best-practices

DRY(Don't Repeat Yourself),
KISS(Keep It Simple, Stupid),
SOLID(Single responsibility, Open-closed, Liskov substitution, Interface segregation, Dependency inversion)

## License

This project is licensed under the [MIT License](https://opensource.org/licenses/MIT).
