@php
  $isExcluded = $egg->exclude_from_updates;
  $hasError = !$isExcluded && $egg->last_update_error;
  $needsCheck = !$isExcluded && !$egg->last_update_check_at;
  $hasUpdate = !$isExcluded && $egg->last_update_hash && $egg->applied_update_hash && $egg->last_update_hash !== $egg->applied_update_hash;
  $isOk = !$isExcluded && !$hasError && !$needsCheck && !$hasUpdate;
@endphp
@if($isExcluded)
  <span class="text-muted"><i class="fa fa-ban" style="margin-right:3px;"></i> Excluded</span>
@elseif($hasError)
  <span class="text-danger"><i class="fa fa-exclamation-circle" style="margin-right:3px;"></i> Error</span>
@elseif($needsCheck)
  <span class="text-warning"><i class="fa fa-clock-o" style="margin-right:3px;"></i> Pending</span>
@elseif($hasUpdate)
  <span class="text-warning"><i class="fa fa-exclamation-triangle" style="margin-right:3px;"></i> Update Available</span>
@elseif($isOk)
  <span class="text-success"><i class="fa fa-check-circle" style="margin-right:3px;"></i> OK</span>
@endif
