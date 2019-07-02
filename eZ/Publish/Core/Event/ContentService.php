<?php

/**
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace eZ\Publish\Core\Event;

use eZ\Publish\API\Repository\ContentService as ContentServiceInterface;
use eZ\Publish\API\Repository\Values\Content\Content;
use eZ\Publish\API\Repository\Values\Content\ContentCreateStruct;
use eZ\Publish\API\Repository\Values\Content\ContentInfo;
use eZ\Publish\API\Repository\Values\Content\ContentMetadataUpdateStruct;
use eZ\Publish\API\Repository\Values\Content\ContentUpdateStruct;
use eZ\Publish\API\Repository\Values\Content\Language;
use eZ\Publish\API\Repository\Values\Content\LocationCreateStruct;
use eZ\Publish\API\Repository\Values\Content\Relation;
use eZ\Publish\API\Repository\Values\Content\VersionInfo;
use eZ\Publish\API\Repository\Values\User\User;
use eZ\Publish\Core\Event\Content\AddRelationEvent;
use eZ\Publish\Core\Event\Content\BeforeAddRelationEvent;
use eZ\Publish\Core\Event\Content\BeforeCopyContentEvent;
use eZ\Publish\Core\Event\Content\BeforeCreateContentDraftEvent;
use eZ\Publish\Core\Event\Content\BeforeCreateContentEvent;
use eZ\Publish\Core\Event\Content\BeforeDeleteContentEvent;
use eZ\Publish\Core\Event\Content\BeforeDeleteRelationEvent;
use eZ\Publish\Core\Event\Content\BeforeDeleteTranslationEvent;
use eZ\Publish\Core\Event\Content\BeforeDeleteVersionEvent;
use eZ\Publish\Core\Event\Content\BeforeHideContentEvent;
use eZ\Publish\Core\Event\Content\BeforePublishVersionEvent;
use eZ\Publish\Core\Event\Content\BeforeRevealContentEvent;
use eZ\Publish\Core\Event\Content\BeforeUpdateContentEvent;
use eZ\Publish\Core\Event\Content\BeforeUpdateContentMetadataEvent;
use eZ\Publish\Core\Event\Content\CopyContentEvent;
use eZ\Publish\Core\Event\Content\CreateContentDraftEvent;
use eZ\Publish\Core\Event\Content\CreateContentEvent;
use eZ\Publish\Core\Event\Content\DeleteContentEvent;
use eZ\Publish\Core\Event\Content\DeleteRelationEvent;
use eZ\Publish\Core\Event\Content\DeleteTranslationEvent;
use eZ\Publish\Core\Event\Content\DeleteVersionEvent;
use eZ\Publish\Core\Event\Content\HideContentEvent;
use eZ\Publish\Core\Event\Content\PublishVersionEvent;
use eZ\Publish\Core\Event\Content\RevealContentEvent;
use eZ\Publish\Core\Event\Content\UpdateContentEvent;
use eZ\Publish\Core\Event\Content\UpdateContentMetadataEvent;
use eZ\Publish\SPI\Repository\Decorator\ContentServiceDecorator;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

class ContentService extends ContentServiceDecorator
{
    /** @var \Symfony\Contracts\EventDispatcher\EventDispatcherInterface */
    protected $eventDispatcher;

    public function __construct(
        ContentServiceInterface $innerService,
        EventDispatcherInterface $eventDispatcher
    ) {
        parent::__construct($innerService);

        $this->eventDispatcher = $eventDispatcher;
    }

    public function createContent(
        ContentCreateStruct $contentCreateStruct,
        array $locationCreateStructs = []
    ): Content {
        $eventData = [
            $contentCreateStruct,
            $locationCreateStructs,
        ];

        $beforeEvent = new BeforeCreateContentEvent(...$eventData);
        if ($this->eventDispatcher->dispatch($beforeEvent)->isPropagationStopped()) {
            return $beforeEvent->getContent();
        }

        $content = $beforeEvent->hasContent()
            ? $beforeEvent->getContent()
            : $this->innerService->createContent($contentCreateStruct, $locationCreateStructs);

        $this->eventDispatcher->dispatch(new CreateContentEvent($content, ...$eventData));

        return $content;
    }

    public function updateContentMetadata(
        ContentInfo $contentInfo,
        ContentMetadataUpdateStruct $contentMetadataUpdateStruct
    ): Content {
        $eventData = [
            $contentInfo,
            $contentMetadataUpdateStruct,
        ];

        $beforeEvent = new BeforeUpdateContentMetadataEvent(...$eventData);
        if ($this->eventDispatcher->dispatch($beforeEvent)->isPropagationStopped()) {
            return $beforeEvent->getContent();
        }

        $content = $beforeEvent->hasContent()
            ? $beforeEvent->getContent()
            : $this->innerService->updateContentMetadata($contentInfo, $contentMetadataUpdateStruct);

        $this->eventDispatcher->dispatch(new UpdateContentMetadataEvent($content, ...$eventData));

        return $content;
    }

    public function deleteContent(ContentInfo $contentInfo): array
    {
        $eventData = [$contentInfo];

        $beforeEvent = new BeforeDeleteContentEvent(...$eventData);
        if ($this->eventDispatcher->dispatch($beforeEvent)->isPropagationStopped()) {
            return $beforeEvent->getLocations();
        }

        $locations = $beforeEvent->hasLocations()
            ? $beforeEvent->getLocations()
            : $this->innerService->deleteContent($contentInfo);

        $this->eventDispatcher->dispatch(new DeleteContentEvent($locations, ...$eventData));

        return $locations;
    }

    public function createContentDraft(
        ContentInfo $contentInfo,
        VersionInfo $versionInfo = null,
        User $creator = null
    ): Content {
        $eventData = [
            $contentInfo,
            $versionInfo,
            $creator,
        ];

        $beforeEvent = new BeforeCreateContentDraftEvent(...$eventData);
        if ($this->eventDispatcher->dispatch($beforeEvent)->isPropagationStopped()) {
            return $beforeEvent->getContentDraft();
        }

        $contentDraft = $beforeEvent->hasContentDraft()
            ? $beforeEvent->getContentDraft()
            : $this->innerService->createContentDraft($contentInfo, $versionInfo, $creator);

        $this->eventDispatcher->dispatch(new CreateContentDraftEvent($contentDraft, ...$eventData));

        return $contentDraft;
    }

    public function updateContent(
        VersionInfo $versionInfo,
        ContentUpdateStruct $contentUpdateStruct
    ): Content {
        $eventData = [
            $versionInfo,
            $contentUpdateStruct,
        ];

        $beforeEvent = new BeforeUpdateContentEvent(...$eventData);
        if ($this->eventDispatcher->dispatch($beforeEvent)->isPropagationStopped()) {
            return $beforeEvent->getContent();
        }

        $content = $beforeEvent->hasContent()
            ? $beforeEvent->getContent()
            : $this->innerService->updateContent($versionInfo, $contentUpdateStruct);

        $this->eventDispatcher->dispatch(new UpdateContentEvent($content, ...$eventData));

        return $content;
    }

    public function publishVersion(VersionInfo $versionInfo, array $translations = Language::ALL): Content
    {
        $eventData = [
            $versionInfo,
            $translations
        ];

        $beforeEvent = new BeforePublishVersionEvent(...$eventData);
        if ($this->eventDispatcher->dispatch($beforeEvent)->isPropagationStopped()) {
            return $beforeEvent->getContent();
        }

        $content = $beforeEvent->hasContent()
            ? $beforeEvent->getContent()
            : $this->innerService->publishVersion($versionInfo, $translations);

        $this->eventDispatcher->dispatch(new PublishVersionEvent($content, ...$eventData));

        return $content;
    }

    public function deleteVersion(VersionInfo $versionInfo): void
    {
        $eventData = [$versionInfo];

        $beforeEvent = new BeforeDeleteVersionEvent(...$eventData);
        if ($this->eventDispatcher->dispatch($beforeEvent)->isPropagationStopped()) {
            return;
        }

        $this->innerService->deleteVersion($versionInfo);

        $this->eventDispatcher->dispatch(new DeleteVersionEvent(...$eventData));
    }

    public function copyContent(
        ContentInfo $contentInfo,
        LocationCreateStruct $destinationLocationCreateStruct,
        VersionInfo $versionInfo = null
    ): Content {
        $eventData = [
            $contentInfo,
            $destinationLocationCreateStruct,
            $versionInfo,
        ];

        $beforeEvent = new BeforeCopyContentEvent(...$eventData);
        if ($this->eventDispatcher->dispatch($beforeEvent)->isPropagationStopped()) {
            return $beforeEvent->getContent();
        }

        $content = $beforeEvent->hasContent()
            ? $beforeEvent->getContent()
            : $this->innerService->copyContent($contentInfo, $destinationLocationCreateStruct, $versionInfo);

        $this->eventDispatcher->dispatch(new CopyContentEvent($content, ...$eventData));

        return $content;
    }

    public function addRelation(
        VersionInfo $sourceVersion,
        ContentInfo $destinationContent
    ): Relation {
        $eventData = [
            $sourceVersion,
            $destinationContent,
        ];

        $beforeEvent = new BeforeAddRelationEvent(...$eventData);
        if ($this->eventDispatcher->dispatch($beforeEvent)->isPropagationStopped()) {
            return $beforeEvent->getRelation();
        }

        $relation = $beforeEvent->hasRelation()
            ? $beforeEvent->getRelation()
            : $this->innerService->addRelation($sourceVersion, $destinationContent);

        $this->eventDispatcher->dispatch(new AddRelationEvent($relation, ...$eventData));

        return $relation;
    }

    public function deleteRelation(
        VersionInfo $sourceVersion,
        ContentInfo $destinationContent
    ): void {
        $eventData = [
            $sourceVersion,
            $destinationContent,
        ];

        $beforeEvent = new BeforeDeleteRelationEvent(...$eventData);
        if ($this->eventDispatcher->dispatch($beforeEvent)->isPropagationStopped()) {
            return;
        }

        $this->innerService->deleteRelation($sourceVersion, $destinationContent);

        $this->eventDispatcher->dispatch(new DeleteRelationEvent(...$eventData));
    }

    public function deleteTranslation(
        ContentInfo $contentInfo,
        $languageCode
    ): void {
        $eventData = [
            $contentInfo,
            $languageCode,
        ];

        $beforeEvent = new BeforeDeleteTranslationEvent(...$eventData);
        if ($this->eventDispatcher->dispatch($beforeEvent)->isPropagationStopped()) {
            return;
        }

        $this->innerService->deleteTranslation($contentInfo, $languageCode);

        $this->eventDispatcher->dispatch(new DeleteTranslationEvent(...$eventData));
    }

    public function hideContent(ContentInfo $contentInfo): void
    {
        $eventData = [$contentInfo];

        $beforeEvent = new BeforeHideContentEvent(...$eventData);
        if ($this->eventDispatcher->dispatch($beforeEvent)->isPropagationStopped()) {
            return;
        }

        $this->innerService->hideContent($contentInfo);

        $this->eventDispatcher->dispatch(new HideContentEvent(...$eventData));
    }

    public function revealContent(ContentInfo $contentInfo): void
    {
        $eventData = [$contentInfo];

        $beforeEvent = new BeforeRevealContentEvent(...$eventData);
        if ($this->eventDispatcher->dispatch($beforeEvent)->isPropagationStopped()) {
            return;
        }

        $this->innerService->revealContent($contentInfo);

        $this->eventDispatcher->dispatch(new RevealContentEvent(...$eventData));
    }
}
