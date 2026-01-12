<?php
// src/State/MediaObjectProcessor.php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Entity\MediaObject;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Vich\UploaderBundle\Storage\StorageInterface;

final readonly class MediaObjectProcessor implements ProcessorInterface
{
    public function __construct(
        #[Autowire(service: 'api_platform.doctrine.orm.state.persist_processor')]
        private ProcessorInterface $persistProcessor,
        private StorageInterface $storage
    ) {
    }

    public function process($data, Operation $operation, array $uriVariables = [], array $context = []): MediaObject
    {
        // Persist l'entité avec Vich qui gère le fichier
        $result = $this->persistProcessor->process($data, $operation, $uriVariables, $context);

        // Génère l'URL publique du fichier
        $result->contentUrl = $this->storage->resolveUri($result, 'file');

        return $result;
    }
}
