<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Entity\PaymentRow;

class DashboardController extends AbstractController
{
    #[Route('/dashboard/customers', name: 'app_dashboard_all_customers')]
    public function GetAllCustomers(): Response
    {
        $stripe = new \Stripe\StripeClient($_ENV["STRIPE_SECRET"]);
        $table = $stripe->customers->all(['limit' => 20]);

        dd($table);
        return $this->render('dashboard/all_customers.html.twig', [
            'controller_name' => 'DashboardController',
        ]);
    }

    #[Route('/dashboard/payments', name: 'app_dashboard_all_payments')]
    public function GetAllPayments(): Response
    {
        $stripe = new \Stripe\StripeClient($_ENV["STRIPE_SECRET"]);
        $paymentIntents = $stripe->paymentIntents->all(['limit' => 20]);
        $paymentRows = [];
        foreach ($paymentIntents->data as $row) {
            $paymentrow = new PaymentRow($row->id, $row->amount, $row->status);
            $paymentrow->setStatus($row->status);
            if($row->status == "succeeded"){$paymentrow->setStatus("Succès");}
            else if($row->status == "requires_capture"){$paymentrow->setStatus("Non capturé");}
            $paymentRows[] = $paymentrow;
        }

        $params = [];
        $params['paymentrows'] = $paymentRows;
        return $this->render('dashboard/all_payments.html.twig', $params);
    }   
    #[Route('/dashboard/details/{id}', name: 'app_dashboard_details')]
    public function GetPageDetails(string $id): Response
    {
        $stripe = new \Stripe\StripeClient($_ENV["STRIPE_SECRET"]);
        $paymentIntents = $stripe->paymentIntents->retrieve(['limit' => 20]);
        $params = [];
        return $this->render('dashboard/all_payments.html.twig', $params);
    }
}