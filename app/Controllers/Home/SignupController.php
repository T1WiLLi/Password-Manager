<?php

namespace Controllers\Home;

use Controllers\Controller;
use Zephyrus\Network\Response;
use Zephyrus\Network\Router\Get;
use Zephyrus\Network\Router\Post;

class SignupController extends Controller
{
    #[Get("/signup")]
    public function signupView(): Response
    {
        return $this->render("signup", ['title' => "Sign up"]);
    }

    #[Get("/verify-email/{token}")]
    public function verifyEmail(string $token): Response
    {
        return new Response();
    }

    #[Post("/signup")]
    public function signup(): Response
    {
        return new Response();
    }
}
