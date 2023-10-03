<?php

declare(strict_types=1);

namespace App\Controller;

use App\Command\DemoCommand;
use App\Dto\RegisterFormDto;
use App\Entity\Product;
use App\Form\Type\RegisterForm;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * @see AppControllerTest
 */
#[AsController]
#[Route(name: 'app_')]
final class AppController extends AbstractController
{
    /**
     * Simple page with some dynamic content.
     */
    #[Route(path: '/', name: 'home')]
    public function home(SerializerInterface $serializer, DemoCommand $demoCommand, LoggerInterface $logger): Response
    {
        $readme = file_get_contents(__DIR__.'/../../README.md');

        $product = $demoCommand->getProductEntity();
        $xml = $demoCommand->getProductXml($product);
        dump($product, $xml);

        // now read it back into a product
        /** @var Product $product2 */
        $product2 = $serializer->deserialize($xml, Product::class, 'xml', ['xml_root_node_name' => 'product'] );
        if ($product2->getDescription() == $product->getDescription()) {
            $logger->info("deserialize success!");
        } else {
            $logger->error($xml . " deserialize did not work :-(");
        }


        return $this->render('home.html.twig',
            compact('xml', 'product', 'product2', 'readme'));
    }

    /**
     * Displays the composer.json file.
     */
    #[Route(path: '/composer', name: 'composer')]
    public function composer(): Response
    {
        $composer = file_get_contents(__DIR__.'/../../composer.json');

        return $this->render('composer.html.twig', compact('composer'));
    }

    /**
     * A simple form.
     */
    #[Route(path: '/form', name: 'form')]
    public function form(Request $request): Response
    {
        $dto = new RegisterFormDto();
        $form = $this->createForm(RegisterForm::class, $dto)->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            // just for the example, the DTO has already been updated
            $dto = $form->getData();
        }

        return $this->render('form.html.twig', compact('form', 'dto'));
    }
}
