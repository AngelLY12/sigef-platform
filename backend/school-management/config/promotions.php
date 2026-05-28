<?php

return [
     'allowed_months' => array_map(
         'intval',
         explode(',', env('SEMESTER_ALLOWED_MONTHS', '1,7'))
     ),
     'max_semester' => 10,
     'force_execution' => (bool) env('PROMOTION_FORCE_EXECUTION', false),

];
