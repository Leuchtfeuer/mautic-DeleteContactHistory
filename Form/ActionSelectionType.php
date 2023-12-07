<?php

declare(strict_types=1);

namespace MauticPlugin\LeuchtfeuerDeleteContactHistoryBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\NotBlank;

class ActionSelectionType extends AbstractType
{
    public const PAGE_HITS = 'Clear all page hits';
    public const EMAIL_OPEN_LINK_CLICKS = 'Clear all email open & link clicks';
    public const FOCUS_ITEMS_STATS = 'Clear all Focus Item stats';
    public const ASSET_DOWNLOADS = 'Clear all asset downloads';
    public const ALL = 'All';


    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add(
            'clearHistory',
            ChoiceType::class,
            [
                'multiple'  => true,
                'required'  => true,
                'label' => 'Select information to be deleted',
                'choices'   => [
                    self::PAGE_HITS => self::PAGE_HITS,
                    self::EMAIL_OPEN_LINK_CLICKS => self::EMAIL_OPEN_LINK_CLICKS,
                    self::FOCUS_ITEMS_STATS => self::FOCUS_ITEMS_STATS,
                    self::ASSET_DOWNLOADS => self::ASSET_DOWNLOADS,
                    self::ALL => self::ALL,
                ],
                'constraints'   => [
                    new NotBlank(
                        ['message' => 'mautic.history.chooseaction.notblank']
                    ),
                ],
            ]
        );
    }
}
