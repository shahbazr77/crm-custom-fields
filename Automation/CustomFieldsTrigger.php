<?php

namespace CRM_Custom_Fields\Automation;

use FluentCrm\App\Services\Funnel\BaseTrigger;
use FluentCrm\App\Services\Funnel\FunnelHelper;
use FluentCrm\Framework\Support\Arr;
use FluentCrm\App\Services\Funnel\FunnelProcessor;
use FluentCrm\App\Models\CustomContactField;

class CustomFieldTrigger extends BaseTrigger {

    public function __construct()
    {
        $this->{'triggerName'} = 'custom-field-integrate';
        $this->{'priority'}     = 15;
        $this->{'actionArgNum'} = 3;
        parent::__construct();
    }

    public function getTrigger()
    {
        return [
            'category'    => __('CRM', 'yodocustom'),
            'label'       => __('Custom Field Integration', 'yodocustom'),
            'description' => __('This will start when Choose Custom Date and Time', 'yodocustom'),
//            'icon'        => 'fc-icon-wp_new_user_signup',
        ];
    }

    public function getFunnelSettingsDefaults() {
        return array(
            'custom_date'  => '',
        );
    }

    public function getSettingsFields($funnel)
    {
        $options = array();
        foreach ( fluentcrm_get_custom_contact_fields() as $field ) {
            $options[] = array(
                'id'    => $field['slug'],
                'title' => $field['label'],
            );
        }
        return array(
            'title'     => __( 'Custom Field Updated', 'yodocustom' ),
            'sub_title' => __( 'This Funnel will start when Data and Time is Compare to the Local Time.', 'yodocustom' ),
            'fields'    => array(
                'custom_date' => array(
                    'type'        => 'select',
                    'options'     => $options,
                    'label'       => __( 'Choose The Custom Date and Time', 'yodocustom' ),
                    'placeholder' => __( 'Please Choose Custom Contact Field', 'yodocustom' ),
                ),
            ),
        );

    }

    /**
     * Get the defaults for the funnel.
     *
     * @since 1.0.0
     *
     * @param FluentCrm\App\Models\Funnel $funnel The funnel.
     */
    public function getFunnelConditionDefaults( $funnel ) {
        return array(
            'custom_date'  => '',
        );
    }

    /**
     * Get the conditional fields for the funnel.
     *
     * @since 1.0.0
     *
     * @param FluentCrm\App\Models\Funnel $funnel The funnel.
     */
    public function getConditionFields( $funnel ) {

        return array(
            'schedule_date'  => array(
                'type'    => 'select',
                'label'   => __('Choose When Trigger Start Dropdown', 'yodocustom'),
                'help'       => __( 'Choose if the Trigger Should Start befor after the choosen field.', 'yodocustom' ),
                'placeholder' => __('Choose When', 'yodocustom'),
                'options' => array(
                    array(
                        'id'    => 'before',
                        'title' => __('Before', 'yodocustom'),
                    ),
                    array(
                        'id'    => 'after',
                        'title' => __('After', 'yodocustom'),
                    ),
                    array(
                        'id'    => 'immediately',
                        'title' => __('Immediately', 'yodocustom'),
                    ),
                ),
                'dependency' => array(
                    'depends_on' => 'custom_date',
                    'operator'   => '!=',
                    'value'      => '',
                ),
            ),
            'day_value'  => array(
                'type'       => 'select',
                'label'      => __('Choose d/h/m', 'yodocustom'),
                'placeholder' => __('Choose d/h/m', 'yodocustom'),
                'options' => array(
                    array(
                        'id'    => 'days',
                        'title' => __('Days', 'yodocustom'),
                    ),
                    array(
                        'id'    => 'hours',
                        'title' => __('Hours', 'yodocustom'),
                    ),
                    array(
                        'id'    => 'minutes',
                        'title' => __('Minutes', 'yodocustom'),
                    ),
                ),
                'dependency' => array(
                    'depends_on' => 'schedule_date',
                    'operator'   => '!=',
                    'value'      => 'immediately',
                ),
            ),
            'number_fields'  => array(
                'type'       => 'input-number',
                'label'      => __('Input Field For Number', 'yodocustom'),
                'help'       => __('Enter the field value that must match to trigger the automation.', 'yodocustom'),
                'wrapper_class' => 'fc_2col_inline pad-r-20 hhhhhhhhhhhhh',
                'dependency' => array(
                    'depends_on' => 'schedule_date',
                    'operator'   => '!=',
                    'value'      => 'immediately',
                ),
            ),
        );




    }

    /**
     * Handle the action.
     *
     * @since 1.0.0
     *
     * @param FluentCrm\App\Models\Funnel $funnel        The funnel.
     * @param array                       $original_args The original arguments.
     */
    public function handle( $funnel, $original_args ) {

        $subscriber   = $original_args[1];
        $updated_data = $original_args[2];

        $will_process = $this->isProcessable( $funnel, $subscriber, $updated_data );

        $will_process = apply_filters( 'fluentcrm_funnel_will_process_' . $this->{'triggerName'}, $will_process, $funnel, $subscriber, $original_args );

        if ( ! $will_process ) {
            return;
        }

        ( new FunnelProcessor() )->startFunnelSequence(
            $funnel,
            array(),
            array(
                'source_trigger_name' => $this->{'triggerName'},
            ),
            $subscriber
        );
    }

    /**
     * Is the action processable?
     *
     * @since 1.0.0
     *
     * @param FluentCrm\App\Models\Funnel     $funnel       The funnel.
     * @param FluentCrm\App\Models\Subscriber $subscriber   The subscriber.
     * @param array                           $updated_data The updated custom fields.
     * @return bool Whether or not the action is processable.
     */
    private function isProcessable( $funnel, $subscriber, $updated_data ) {
        $field = Arr::get( $funnel->settings, 'custom_date' );

        // Check if the correct field was updated.
        if ( ! array_key_exists( $field, $updated_data ) ) {
            return false;
        }
        $custom_date = Carbon::parse($updated_data[$field]);
        $current_date = Carbon::now();

        $schedule_date = Arr::get($funnel->conditions, 'schedule_date');
        $day_value = Arr::get($funnel->conditions, 'day_value');
        $number_fields = Arr::get($funnel->conditions, 'number_fields');

        if ($schedule_date === 'before') {
            $trigger_date = $custom_date->sub($number_fields, $day_value);
        } elseif ($schedule_date === 'after') {
            $trigger_date = $custom_date->add($number_fields, $day_value);
        } else {
            $trigger_date = $custom_date;
        }

        if ($current_date->gte($trigger_date)) {
            return true;
        }

        // check run_only_once.
        if ( $subscriber && FunnelHelper::ifAlreadyInFunnel( $funnel->id, $subscriber->id ) ) {
            if ( 'yes' === Arr::get( $funnel->conditions, 'run_multiple' ) ) {
                FunnelHelper::removeSubscribersFromFunnel( $funnel->id, array( $subscriber->id ) );
            } else {
                return false;
            }
        }

        return true;

    }

}
