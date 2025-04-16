<?php

namespace App\Form;

use App\Entity\Lieu;
use App\Form\EventListener\LieuFormListener;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use App\Service\GeoApiService;
class LieuType extends AbstractType
{


public function __construct(
    private GeoApiService $geoApiService
) {
}

public function buildForm(FormBuilderInterface $builder, array $options): void
{
    $regions = $this->geoApiService->getRegions();

    if (!empty($options['region'])) {
        $departements = $this->geoApiService->getDepartementsByRegion($options['region']);
        $departementFormatted = [];
        array_walk($departements, function ($departement) use (&$departementFormatted) {
            $departementFormatted[$departement['nom']] = $departement['code'];
        });
    }
    if (!empty($options['departement'])) {
        $cities = $this->geoApiService->getVillesByDepartement($options['departement']);
        $cityFormatted = [];
        array_walk($cities, function ($city) use (&$cityFormatted) {
            $cityFormatted[$city['nom']] = $city['nom'];
        });
    }

    $builder
        ->add('name', TextType::class, [
            'label' => 'Nom du lieu',
        ])
        ->add('postaleCode', TextType::class, [
            'label' => 'Code postal',
            'attr' => [
                'data-geo-api-target' => 'postcode',
                'readonly' => true, // Désactiver le champ
            ],
        ])
        ->add('region', ChoiceType::class, [
            'label' => 'Région',
            'choices' => $regions, // Les régions sont définies statiquement
            'placeholder' => 'Sélectionnez une région',
            'attr' => [
                'data-geo-api-target' => 'region',
                'data-action' => 'change->geo-api#get_departement',

            ],
        ])
        ->add('departement', ChoiceType::class, [
            'label' => 'Département',
            'choices' => $departementFormatted ?? [], // Pas de choix définis statiquement
            'choice_loader' => null, // Désactive le chargement des choix
            'placeholder' => 'Sélectionnez un département',
            'attr' => [
                'data-geo-api-target' => 'departement',
                'data-action' => 'change->geo-api#get_city',
            ],
        ])
        ->add('city', ChoiceType::class, [
            'label' => 'Ville',
            'choices' => $cityFormatted ?? [], // Pas de choix définis statiquement
            'choice_loader' => null, // Désactive le chargement des choix
            'placeholder' => 'Sélectionnez une ville',
            'attr' => [
                'data-geo-api-target' => 'city',
                'data-action' =>'change->geo-api#get_postcode'
            ],
        ])
        ->add('street', TextType::class, [
            'label' => 'Rue',
        ])
        ->add('latitude', TextType::class, [
            'label' => 'Latitude',
            'required' => false,
        ])
        ->add('longitude', TextType::class, [
            'label' => 'Longitude',
            'required' => false,
        ])
        ;

    // Ajoutez le listener avec le service GeoApiService
   //$builder->addEventSubscriber(new LieuFormListener($this->geoApiService));
}
public function configureOptions(OptionsResolver $resolver): void
{
    $resolver->setDefaults([
        'data_class' => Lieu::class,
        'region' => null,
        'departement' => null,
    ]);
}
}