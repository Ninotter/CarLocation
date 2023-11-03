<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Entity\CarForm;
use Exception;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Stripe\Stripe;
use Stripe\Checkout\Session;
use SendinBlue\Client\Api\TransactionalEmailsApi;
use GuzzleHttp\Client;
use SendinBlue\Client\Configuration;


class FormController extends AbstractController
{
  #[Route('/form', name: 'app_form')]
  public function index(Request $request): Response
  {
    $params = [];

    $carForm = new CarForm();
    $form = $this->createFormBuilder($carForm)
      ->add('nom', TextType::class)
      ->add('prenom', TextType::class)
      ->add('age', NumberType::class)
      ->add(
        'ville',
        ChoiceType::class,
        ['choices' => ["Paris" => "Paris", "Lyon" => "Lyon", "Marseille" => "Marseille"]]
      )
      ->add(
        'vehicule',
        ChoiceType::class,
        ['choices' => ["Aston Martin" => "Aston Martin", "Bentley" => "Bentley", "Cadillac" => "Cadillac", "Ferrari" => "Ferrari", "Jaguar" => "Jaguar"]]
      )
      ->add('token', TextType::class, ['data' => '6d9e9d4d-925d-496b-af3e-bdac6cec0477'])
      ->add('button', SubmitType::class, ['label' => 'Envoyer'])
      ->getForm();


    $form->handleRequest($request);
    if ($form->isSubmitted() && $form->isValid()) {
      // $form->getData() holds the submitted values
      // but, the original `$task` variable has also been updated
      $carForm = $form->getData();

      $carFormArray = [
        "nom" => $carForm->getNom(),
        "prenom" => $carForm->getPrenom(),
        "age" => $carForm->getAge(),
        "ville" => $carForm->getVille(),
        "vehicule" => $carForm->getVehicule(),
        "token" => $carForm->getToken()
      ];
      $jwt = JWT::encode($carFormArray, "SouquezLesArtibuses", 'HS256');
      $marque_vehicule = $carForm->getVehicule();
      $domain = $request->getHost();
      $port = $request->getPort();
      $capture_method = "manual";
      Stripe::setApiKey($_ENV["STRIPE_SECRET"]);

      $session_parameters = [
        "submit_type" => "pay",
        "payment_method_types" => ["card"],
        "line_items" => [
          [

            "quantity" => 1,
            "price_data" => [
              "currency" => "eur",
              "unit_amount_decimal" => 500_000,
              "product_data" => [
                "name" => "Location $marque_vehicule",
                "images"=> [
                  "https://cdn.motor1.com/images/mgl/NGGZon/s3/koenigsegg-gemera.jpg"
                ]
              ]
            ]
          ]
        ],
        "mode" => "payment",
        "payment_intent_data" => [
          "metadata" => [
            "jwt" => "$jwt"
          ],
          "capture_method" => "$capture_method",
          "setup_future_usage" => "off_session"
        ],
        "success_url" => "http://$domain:$port/form/transaction-validee?transaction_id={CHECKOUT_SESSION_ID}&jwt=$jwt",
        "cancel_url" => "http://$domain:$port/form",
        "customer_creation" => "always"
      ];
      // dd($session_parameters);
      $checkout_session = Session::create($session_parameters);
      return $this->redirect($checkout_session->url, 303);
    }

    $params += ['form' => $form,];
    return $this->render('form/index.html.twig', $params);
  }

  #[Route('/form/transaction-validee', name: 'app_form_success')]
  public function SuccessTransaction(Request $request): Response
  {
    $idtransaction = $request->get('transaction_id');
    $jwt = $request->get('jwt');
    if($jwt == null || $idtransaction == null){
      return $this->redirect('/form');
    }
    $decodedToken = null;
    try{
      $decodedToken = JWT::decode($jwt, new Key("SouquezLesArtibuses", 'HS256'));
      $decodedToken = (array) $decodedToken;
    }catch(Exception $e){
      return $this->redirect('/form');
    }
    $stripe = new \Stripe\StripeClient($_ENV["STRIPE_SECRET"]);
    try{
      $checkout_session = $stripe->checkout->sessions->retrieve($idtransaction, ['expand' => ['payment_intent.payment_method']]);
    }
    catch(Exception $e){
      return $this->redirect('/form');
    }
    if(isset($_POST['send_mail'])){
      $email = $checkout_session->customer_details->email;
      $this->SendEmail($email, $idtransaction, $decodedToken['nom'], $decodedToken['prenom'], $decodedToken['age'], $decodedToken['ville'], $decodedToken['vehicule'], $decodedToken['token'], $checkout_session->amount_total, $checkout_session->payment_intent->payment_method->card->last4);
    }

    $params = [];
    $params += ['idtransaction' => $idtransaction];
    $params += ['jwt' => $jwt];
    $params += ['nom' => $decodedToken['nom']];
    $params += ['prenom' => $decodedToken['prenom']];
    $params += ['age' => $decodedToken['age']];
    $params += ['ville' => $decodedToken['ville']];
    $params += ['vehicule' => $decodedToken['vehicule']];
    $params += ['token' => $decodedToken['token']];
    $params += ['email' => $checkout_session->customer_details->email];
    $params += ['amount' => $checkout_session->amount_total];
    $params += ['last4' => $checkout_session->payment_intent->payment_method->card->last4];

    return $this->render('form/form_success.html.twig', $params);
  }

  private function SendEmail($email, $idtransaction, $nom, $prenom, $age, $ville, $vehicule, $token, $amount, $last4){
    // Configure API key authorization: api-key
    $config = Configuration::getDefaultConfiguration()->setApiKey('api-key', $_ENV["SENDINBLUE_API_KEY"]);
    $apiInstance = new TransactionalEmailsApi(
        new Client(),
        $config
    );
    $sendSmtpEmail = new \SendinBlue\Client\Model\SendSmtpEmail();
    $sendSmtpEmail['to'] = array(array('email'=>$email, 'name'=> $prenom . ' ' . $nom));
    $sendSmtpEmail['templateId'] = 1;
    $sendSmtpEmail['params'] = array('prenom'=> $prenom, 'nom'=>$nom, 'age'=>$age, 'ville'=>$ville, 'email'=>$email ,'vehicule'=>$vehicule, 'token'=>$token, 'amount'=>$amount, 'last4'=>$last4, 'transaction_id'=>$idtransaction);
    $sendSmtpEmail['headers'] = array('X-Mailin-custom'=>'custom_header_1:custom_value_1|custom_header_2:custom_value_2');
    
    try {
        $result = $apiInstance->sendTransacEmail($sendSmtpEmail);
        return true;
    } catch (Exception $e) {
        return false;
    }
  }
}
