<?php

namespace App\Command;

use App\Entity\Property;
use App\Entity\Product;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Serializer\Encoder\XmlEncoder;
use Symfony\Component\Serializer\Mapping\Factory\ClassMetadataFactory;
use Symfony\Component\Serializer\Mapping\Loader\AnnotationLoader;
use Symfony\Component\Serializer\NameConverter\MetadataAwareNameConverter;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Serializer\SerializerInterface;


class DemoCommand extends Command
{
    protected static $defaultName = 'app:bug';

    public function __construct(private SerializerInterface $serializer, string $name = null)
    {
        parent::__construct($name);
    }

    protected function configure()
    {
        $this
            ->setDescription('Show the bug')
        ;
    }

    public function getProductEntity(): Product
    {
        $product = (new Product())
            ->setDescription("Wool sweater")
            ->addProperty((new Property())->setName('color')->setValue('blue'))
            ->addProperty((new Property())->setName('size')->setValue('small'))
            ;
        return $product;
    }

    public function getProductXml(Product $product): string
    {
        $context = [
            'xml_root_node_name' => 'product',
            'groups' => ['xml'],
            'xml_standalone' => false,
            'xml_format_output' => true,
        ];
        return $this->serializer->serialize($product, 'xml', $context);
    }


    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $product = $this->getProductEntity();
        $xml = $this->getProductXml($product);

        $io->success("Serializing as XML works as expected!");
        $io->writeln($xml);

        // now read it back into a product
        /** @var Product $product2 */
        $product2 = $this->serializer->deserialize($xml, Product::class, 'xml', ['xml_root_node_name' => 'product'] );
        if ($product2->getDescription() == $product->getDescription()) {
            $io->success("deserialize success! " . json_encode($product2, JSON_PRETTY_PRINT));
        } else {
            $io->error(" deserialize did not work :-(" . json_encode($product2, JSON_PRETTY_PRINT));
        }

//        $this->trySomeHacks($io, $product);

        return 0;
    }

    private function trySomeHacks(SymfonyStyle $io)
    {
        $classMetadataFactory = new ClassMetadataFactory(new AnnotationLoader(new AnnotationReader()));
        $metadataAwareNameConverter = new MetadataAwareNameConverter($classMetadataFactory);

        $encoders = [new XmlEncoder()];
        $normalizers = [new ObjectNormalizer($classMetadataFactory, $metadataAwareNameConverter)];
        $serializer = new Serializer($normalizers, $encoders);

        $xml = "<property name='length'>32</property>";
        $object = $serializer->deserialize($xml, Property::class, 'xml');

        if ($object->getName() === 'length') {
            $io->success($xml . ' Works!');
        } else {
            $io->error($xml . ' Not working');
        }

        $product = $this->getProductEntity();
        $xml = $this->getProductXml($product);

        $deserializedProduct = $serializer->deserialize($xml, Product::class, 'xml');
        if ($deserializedProduct->getDescription() === $product->getDescription()) {
            $io->success("deserialize an attribute with ObjectNormalizer success, using @SerializedName in Entity class!");
        } else {
            $io->error("deserialize with ObjectNormalizer did not work :-(");
        }

        if ($deserializedProduct->getProperties()->count()  === 2 ) {
            $io->success("2 Properties deserialized ");
        } else {
            $io->error("deserialize Properties did not work.");
        }

    }
}
