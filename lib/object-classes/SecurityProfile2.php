<?php

class SecurityProfile2
{


    public function cloud_inline_analysis_best_practice()
    {
        $bestpractise = FALSE;

        if( $this->secprof_type != 'spyware' and $this->secprof_type != 'vulnerability' and $this->secprof_type != 'virus' )
            return null;

        if( isset($this->cloud_inline_analysis_enabled) && $this->cloud_inline_analysis_enabled )
        {
            if( isset($this->additional['mica-engine-vulnerability-enabled']) )
            {

                foreach( $this->additional['mica-engine-vulnerability-enabled'] as $name)
                {
                    if( $name['inline-policy-action'] == "reset-both" )
                        $bestpractise = TRUE;
                    else
                        return FALSE;
                }
            }

            if( isset($this->additional['mica-engine-spyware-enabled']) )
            {
                foreach( $this->additional['mica-engine-spyware-enabled'] as $name)
                {
                    if( $name['inline-policy-action'] == "reset-both" )
                        $bestpractise = TRUE;
                    else
                        return FALSE;
                }
            }
        }

        //AV iii) Wildfire Inline ML Tab
        //- all models must be set to 'enable (inherit per-protocol actions)'
        if( isset($this->additional['mlav-engine-filebased-enabled']) )
        {
            foreach( $this->additional['mlav-engine-filebased-enabled'] as $name)
            {
                if( $name['mlav-policy-action'] == "enable" )
                    $bestpractise = TRUE;
                else
                    return FALSE;
            }
        }

        return $bestpractise;
    }

}

