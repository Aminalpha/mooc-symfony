<?php

namespace App\Form;

use App\Entity\Advert;
use App\Form\ImageType;
use App\Form\CategoryType;
use Symfony\Component\Form\FormEvent;
use App\Repository\CategoryRepository;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;

class AdvertType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $pattern = 'I%';
        $builder
            ->add('title',          TextType::class)
            ->add('author',         TextType::class)
            ->add('content',        TextareaType::class)
            // ->add('published',      CheckboxType::class, array('required' => false)  )
            ->add('image',          ImageType::class)
            ->add('categories', EntityType::class, array(
                'class' => 'App\Entity\Category',
                'choice_label' => 'name',
                'multiple' => true,
                'expanded' => true,
                'query_builder' => function(CategoryRepository $repository) use($pattern){
                    return $repository->getLikeQueryBuilder($pattern);
                }
            ))
            ->add('save',           SubmitType::class)
        ;

        $builder->addEventListener(
            FormEvents::PRE_SET_DATA,
            function(FormEvent $event) {
                $advert = $event->getData();

                if (null === $advert) {
                    return;
                }
                if(!$advert->getPublished() || null === $advert->getId()) {
                    $event->getForm()->add('published', checkboxType::class, array('required' => false));
                }
                else {
                    $event->getForm()->remove('published');
                }
            }
        );
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Advert::class,
        ]);
    }
}
