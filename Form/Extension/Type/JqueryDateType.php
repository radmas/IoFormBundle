<?php

/*
 * This file is part of the IoFormBundle package
 *
 * (c) Alessio Baglio <io.alessio@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Io\FormBundle\Form\Extension\Type;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\HttpFoundation\Session\Session;

class JqueryDateType extends DateType {
    /**
     * @param Session $session
     */
    public function __construct(Session $session) {
        $this->session = $session;
    }
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options) {

        $changemonth = $options['changeMonth'];
        $changeyear = $options['changeYear'];
        $mindate = $options['minDate'];
        $maxdate = $options['maxDate'];
        $buttonImage = $options['buttonImage'];

        $builder->setAttribute('changemonth', $changemonth);
        $builder->setAttribute('changeyear', $changeyear);
        $builder->setAttribute('mindate', $mindate);
        $builder->setAttribute('maxdate', $maxdate);
        $builder->setAttribute('buttonImage', $buttonImage);

        parent::buildForm($builder, $options);
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver) {
        parent::setDefaultOptions($resolver);
        //Works only with single text
        $resolver
                ->setDefaults(
                        array('widget' => 'single_text',
                                'changeMonth' => 'false',
                                'changeYear' => 'false', 'minDate' => null,
                                'maxDate' => null, 'buttonImage' => null));
    }

    /**
     * {@inheritdoc}
     */
    public function getName() {
        return 'jquery_date';
    }

    /**
     * {@inheritdoc}
     */
    public function buildViewBottomUp(FormView $view, FormInterface $form) {
        $view->set('widget', $form->getAttribute('widget'));

        $pattern = $form->getAttribute('formatter')->getPattern();
        $format = $pattern;

        if ($view->hasChildren()) {

            // set right order with respect to locale (e.g.: de_DE=dd.MM.yy; en_US=M/d/yy)
            // lookup various formats at http://userguide.icu-project.org/formatparse/datetime
            if (preg_match('/^([yMd]+).+([yMd]+).+([yMd]+)$/', $pattern)) {
                $pattern = preg_replace(array('/y+/', '/M+/', '/d+/'),
                        array('{{ year }}', '{{ month }}', '{{ day }}'),
                        $pattern);
            } else {
                // default fallback
                $pattern = '{{ year }}-{{ month }}-{{ day }}';
            }
        }

        $view->set('date_pattern', $pattern);
        $view->set('date_format', $this->convertJqueryDate($pattern));
        $view->set('change_month', $form->getAttribute('changemonth'));
        $view->set('change_year', $form->getAttribute('changeyear'));
        $view->set('min_date', $form->getAttribute('mindate'));
        $view->set('max_date', $form->getAttribute('maxdate'));
        $view->set('buttonImage', $form->getAttribute('buttonImage'));
        $view->set('locale', $this->session->getLocale());
    }

    protected function convertJqueryDate($pattern) {
        $format = $pattern;
        //jquery use a different syntax, have to replace
        //  php    jquery
        //  MM      mm
        //  MMM     M
        //  MMMM    MM
        //  y       yy

        if (strpos($format, "MMM") > 0) {
            $format = str_replace("MMM", "M", $format);
        } else {
            $format = str_replace("MM", "mm", $format);
        }
        $format = str_replace("LLL", "M", $format);
        $format = str_replace("y", "yy", $format);

        return $format;
    }

}
