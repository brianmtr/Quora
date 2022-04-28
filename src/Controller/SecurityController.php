<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\UserType;
use App\Entity\ResetPassword;
use Doctrine\ORM\EntityManager;
use App\Repository\UserRepository;
use App\Security\LoginFormAuthenticator;
use Doctrine\ORM\EntityManagerInterface;
use App\Repository\ResetPasswordRepository;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Constraints\DateTime;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasher;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\RateLimiter\RateLimiterFactory;
use Symfony\Component\Security\Http\Authentication\UserAuthenticatorInterface;
use Symfony\Component\Validator\Constraints\Length;

class SecurityController extends AbstractController
{
    #[Route(path: '/signup', name: 'signup')]
    public function signup(UserAuthenticatorInterface $userAuthenticator, LoginFormAuthenticator $loginForm, Request $request, EntityManagerInterface $em, UserPasswordHasherInterface $passwordHasher, MailerInterface $mailer) {

        $user = new User();
        $userForm = $this->createForm(UserType::class, $user);
        $userForm->handleRequest($request);
        if ($userForm->isSubmitted() && $userForm->isValid()) {
            $hash = $passwordHasher->hashPassword($user, $user->getPassword());
            $user->setPassword($hash);
            $user->setPassword($hash);
            $em->persist($user);
            $em->flush();
            $this->addFlash('success', 'Bienvenue sur Wonder !');
            $email = new TemplatedEmail();
            $email->to($user->getEmail())
                  ->subject('Bienvenue sur Wonder')
                  ->htmlTemplate('@email_templates/welcome.html.twig')
                  ->context([
                'username' => $user->getFirstname()
            ]);
            $mailer->send($email);

            return $userAuthenticator->authenticateUser($user, $loginForm, $request);
        }

        return $this->render('security/signup.html.twig', ['form' => $userForm->createView()]);
    }


    #[Route(path: '/login', name: 'login')]
    public function login(AuthenticationUtils $authenticationUtils): Response
    {
        if ($this->getUser()) {
            return $this->redirectToRoute('home');
        }

        // get the login error if there is one
        $error = $authenticationUtils->getLastAuthenticationError();
        // last username entered by the user
        $lastUsername = $authenticationUtils->getLastUsername();

        return $this->render('security/login.html.twig', ['last_username' => $lastUsername, 'error' => $error]);
    }

    #[Route(path: '/logout', name: 'logout')]
    public function logout(): void
    {
    }
    #[route('/reset-password/{token}', name: 'reset-password')]
    public function resetPassword(UserPasswordHasherInterface $userPasswordHasher, Request $request, EntityManagerInterface $em, string $token, ResetPasswordRepository $resetPasswordRepository)
    {

        $resetPassword = $resetPasswordRepository->findOneBy(['token' => sha1($token)]);
        if (!$resetPassword || $resetPassword->getExpiredAt() < new DateTime('now'))  {
            if ($resetPassword) {
                $em->remove($resetPassword);
                $em->flush();                
            }
            $this->addFlash('error', 'Votre demande est expiré,  veuillez refaire une demande.');
            
            return $this->redirectToRoute('login');
        }

        $passwordForm = $this->createFormBuilder()
                             ->add('password', PasswordType::class, [
                                 'label'=> 'Nouveau mot de passe',
                                 'constraints' => [
                                    new Length([
                                        'min' => 6,
                                        'minMessage' => 'Le mot de passe doit faire au moins 6 caractères.'
                                    ]),
                                    new NotBlank([
                                        'message' => 'Veuillez renseigner un mot de passe.'
                                    ])
                                 ]
                             ])
                             ->getForm();
        
        $passwordForm->handleRequest($request);
        if ($passwordForm->isSubmitted() && $passwordForm->isValid()) {
           $password = $passwordForm->get('password')->getData();
           $user = $resetPassword->getUser();
           $hash = $userPasswordHasher->hashPassword($user, $password);
           $user->setPassword($hash);
           $em->remove($resetPassword);
           $em->flush();
           $this->addFlash('success', 'Votre mot de passe a été modifié.');
           return $this->redirectToRoute('login');
        }

        return $this->render('security/reset-password-form.html.twig', [
            'form'  => $passwordForm->createView()
        ]);
    }

    #[route('/reset-password-request', name: 'reset-password-request')]
    public function resetPasswordRequest(RateLimiterFactory $passwordRecoveryLimiter, MailerInterface $mailer, Request $request, UserRepository $userRepository, ResetPasswordRepository $resetPasswordRepository, EntityManagerInterface $em) {
        
        // $limiter = $passwordRecoveryLimiter->create($request->getClientIp());
        // if ($limiter->consume(1)->isAccepted()) {
        //     $this->addFlash('error', 'Vous devez attendre 1 heures pour refaire une tentative');
        //     return $this->redirectToRoute('login');
        // }
        
        $emailForm = $this->createFormBuilder()->add('email', EmailType::class, [
            'constraints' => [
                new NotBlank([
                    'message' => 'Veuillez renseigner votre email'
                ])
            ]
        ])->getForm();

        $emailForm->handleRequest($request);
        if($emailForm->isSubmitted() && $emailForm->isValid()) {
            $emailValue = $emailForm->get('email')->getData();

            $user = $userRepository->findOneBy(['email' => $emailValue]);
            if ($user) {
                $oldResetPassword = $resetPasswordRepository->findOneBy(['user' => $user]);
                if ($oldResetPassword) {
                    $em->remove($oldResetPassword);
                    $em->flush();
                }
                $resetPassword = new ResetPassword();
                $resetPassword->setUser($user);
                $resetPassword->setExpiredAt(new \DateTimeImmutable('+2 hours'));
                $token = substr(str_replace(['+', '/', '='], '', base64_encode(random_bytes(30))), 0, 20);
                $resetPassword->setToken(sha1($token));
                $em->persist($resetPassword);
                $em->flush();
                $email = new TemplatedEmail();
                $email->to($emailValue)
                      ->subject('Demande de réinitialisation de mot de passe')
                      ->htmlTemplate('@email_templates/reset-password-request.html.twig')
                      ->context([
                          'token' => $token
                      ]);
                $mailer->send($email);
            }
            $this->addFlash('success', 'un email vous a été envoyer pour réinitialiser votre mot de passe');
            return $this->redirectToRoute('home');
        }

        return $this->render('security/reset-password-request.html.twig', [
            'form' => $emailForm->createView()
        ]);
    }
}
