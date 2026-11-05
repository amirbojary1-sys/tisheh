<?php

return ['reservation_hours' => max(1, (int) env('ORDER_RESERVATION_HOURS', 24))];
