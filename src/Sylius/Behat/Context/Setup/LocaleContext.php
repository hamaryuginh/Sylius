<?php

/*
 * This file is part of the Sylius package.
 *
 * (c) Paweł Jędrzejewski
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sylius\Behat\Context\Setup;

use Behat\Behat\Context\Context;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Persistence\ObjectManager;
use Sylius\Component\Core\Model\ChannelInterface;
use Sylius\Behat\Service\SharedStorageInterface;
use Sylius\Component\Locale\Converter\LocaleConverterInterface;
use Sylius\Component\Locale\Model\LocaleInterface;
use Sylius\Component\Resource\Factory\FactoryInterface;
use Sylius\Component\Resource\Repository\RepositoryInterface;

/**
 * @author Arkadiusz Krakowiak <arkadiusz.krakowiak@lakion.com>
 */
final class LocaleContext implements Context
{
    /**
     * @var SharedStorageInterface
     */
    private $sharedStorage;

    /**
     * @var FactoryInterface
     */
    private $localeFactory;

    /**
     * @var RepositoryInterface
     */
    private $localeRepository;

    /**
     * @var ObjectManager
     */
    private $localeManager;

    /**
     * @var ObjectManager
     */
    private $channelManager;

    /**
     * @var LocaleConverterInterface
     */
    private $localeConverter;

    /**
     * @param SharedStorageInterface $sharedStorage
     * @param LocaleConverterInterface $localeConverter
     * @param FactoryInterface $localeFactory
     * @param RepositoryInterface $localeRepository
     * @param ObjectManager $localeManager
     * @param ObjectManager $channelManager
     */
    public function __construct(
        SharedStorageInterface $sharedStorage,
        LocaleConverterInterface $localeConverter,
        FactoryInterface $localeFactory,
        RepositoryInterface $localeRepository,
        ObjectManager $localeManager,
        ObjectManager $channelManager
    ) {
        $this->sharedStorage = $sharedStorage;
        $this->localeConverter = $localeConverter;
        $this->localeFactory = $localeFactory;
        $this->localeRepository = $localeRepository;
        $this->localeManager = $localeManager;
        $this->channelManager = $channelManager;
    }

    /**
     * @Given the store has locale :localeCode
     * @Given the store is( also) available in :localeCode
     */
    public function theStoreHasLocale($localeCode)
    {
        $locale = $this->provideLocale($localeCode);

        $this->saveLocale($locale);
    }

    /**
     * @Given the store has disabled locale :localeCode
     * @Given the locale :localeCode is disabled (as well)
     * @Given the locale :localeCode gets disabled
     * @Given language :localeCode is disabled
     */
    public function theStoreHasDisabledLocale($localeCode)
    {
        $locale = $this->provideLocale($localeCode);
        $locale->disable();

        $this->saveLocale($locale);
    }

    /**
     * @Given /^(that channel) allows to shop using the "([^"]+)" locale$/
     * @Given /^(that channel) allows to shop using "([^"]+)" and "([^"]+)" locales$/
     * @Given /^(that channel) allows to shop using "([^"]+)", "([^"]+)" and "([^"]+)" locales$/
     */
    public function thatChannelAllowsToShopUsingAndLocales(
        ChannelInterface $channel,
        $firstLocaleName,
        $secondLocaleName = null,
        $thirdLocaleName = null
    ) {
        $locales = new ArrayCollection();

        foreach ([$firstLocaleName, $secondLocaleName, $thirdLocaleName] as $localeName) {
            if (null === $localeName) {
                break;
            }

            $locales[] = $this->provideLocale($this->localeConverter->convertNameToCode($localeName));
        }

        $channel->setLocales($locales);

        $this->channelManager->flush();
    }

    /**
     * @Given /^(it) uses the "([^"]+)" locale by default$/
     */
    public function itUsesTheLocaleByDefault(ChannelInterface $channel, $localeName)
    {
        $locale = $this->provideLocale($this->localeConverter->convertNameToCode($localeName));

        $this->localeManager->flush();

        $channel->addLocale($locale);
        $channel->setDefaultLocale($locale);

        $this->channelManager->flush();
    }

    /**
     * @param string $localeCode
     *
     * @return LocaleInterface
     */
    private function createLocale($localeCode)
    {
        /** @var LocaleInterface $locale */
        $locale = $this->localeFactory->createNew();
        $locale->setCode($localeCode);

        return $locale;
    }

    /**
     * @param string $localeCode
     *
     * @return LocaleInterface
     */
    private function provideLocale($localeCode)
    {
        $locale = $this->localeRepository->findOneBy(['code' => $localeCode]);
        if (null === $locale) {
            /** @var LocaleInterface $locale */
            $locale = $this->createLocale($localeCode);

            $this->localeRepository->add($locale);
        }

        return $locale;
    }

    /**
     * @param LocaleInterface $locale
     */
    private function saveLocale(LocaleInterface $locale)
    {
        $this->sharedStorage->set('locale', $locale);
        $this->localeRepository->add($locale);
    }
}
