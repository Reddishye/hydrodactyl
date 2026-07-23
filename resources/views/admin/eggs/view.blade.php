@extends('layouts.admin')

@section('title')
    Nests &rarr; Egg: {{ $egg->name }}
@endsection

@section('content-header')
    <h1>{{ $egg->name }}<small>{{ str_limit($egg->description, 50) }}</small></h1>
    <ol class="breadcrumb">
        <li><a href="{{ route('admin.index') }}">Admin</a></li>
        <li><a href="{{ route('admin.nests') }}">Nests</a></li>
        <li><a href="{{ route('admin.nests.view', $egg->nest->id) }}">{{ $egg->nest->name }}</a></li>
        <li class="active">{{ $egg->name }}</li>
    </ol>
@endsection

@section('content')
<div class="row">
    <div class="col-xs-12">
        <div class="nav-tabs-custom nav-tabs-floating">
            <ul class="nav nav-tabs">
                <li class="active"><a href="{{ route('admin.nests.egg.view', $egg->id) }}">Configuration</a></li>
                <li><a href="{{ route('admin.nests.egg.variables', $egg->id) }}">Variables</a></li>
                <li><a href="{{ route('admin.nests.egg.scripts', $egg->id) }}">Install Script</a></li>
            </ul>
        </div>
    </div>
</div>
{{-- (manual upload moved to Updates section below) --}}
<form action="{{ route('admin.nests.egg.view', $egg->id) }}" method="POST">
    <div class="row">
        <div class="col-xs-12">
            <div class="box">
                <div class="box-header with-border">
                    <h3 class="box-title">Configuration</h3>
                </div>
                <div class="box-body">
                    <div class="row">
                        <div class="col-sm-6">
                            <div class="form-group">
                                <label for="pName" class="control-label">Name <span class="field-required"></span></label>
                                <input type="text" id="pName" name="name" value="{{ $egg->name }}" class="form-control" />
                                <p class="text-muted small">A simple, human-readable name to use as an identifier for this Egg.</p>
                            </div>
                            <div class="form-group">
                                <label for="pUuid" class="control-label">UUID</label>
                                <input type="text" id="pUuid" readonly value="{{ $egg->uuid }}" class="form-control" />
                                <p class="text-muted small">This is the globally unique identifier for this Egg which the Daemon uses as an identifier.</p>
                            </div>
                            <div class="form-group">
                                <label for="pAuthor" class="control-label">Author</label>
                                <input type="text" id="pAuthor" readonly value="{{ $egg->author }}" class="form-control" />
                                <p class="text-muted small">The author of this version of the Egg. Uploading a new Egg configuration from a different author will change this.</p>
                            </div>
                            <div class="form-group">
                                <label for="pUpdateUrl" class="control-label">Update URL</label>
                                <input type="url" id="pUpdateUrl" name="update_url" value="{{ $egg->update_url }}" class="form-control" placeholder="https://raw.githubusercontent.com/..." />
                                <p class="text-muted small">
                                    URL to Pelican-compatible egg JSON for auto-updates. Leave empty to disable.
                                    Only hosts in <code>ALLOWED_EGG_HOSTS</code> env are allowed when fetching.
                                </p>
                            </div>
                            <div class="form-group">
                                <div class="checkbox checkbox-primary no-margin-bottom">
                                    <input id="pExcludeUpdates" name="exclude_from_updates" type="checkbox" value="1" @if($egg->exclude_from_updates) checked @endif />
                                    <label for="pExcludeUpdates" class="strong">Exclude from auto-updates</label>
                                    <p class="text-muted small">
                                        If checked, this egg will be skipped by the scheduled auto-updater.
                                        Manual checks from the panel will still work.
                                    </p>
                                </div>
                            </div>
                            <div class="form-group">
                                <label for="pDockerImage" class="control-label">Docker Images <span class="field-required"></span></label>
                                <textarea id="pDockerImages" name="docker_images" class="form-control" rows="4">{{ implode(PHP_EOL, $images) }}</textarea>
                                <p class="text-muted small">
                                    The docker images available to servers using this egg. Enter one per line. Users
                                    will be able to select from this list of images if more than one value is provided.
                                    Optionally, a display name may be provided by prefixing the image with the name
                                    followed by a pipe character, and then the image URL. Example: <code>Display Name|ghcr.io/my/egg</code>
                                </p>
                            </div>
                            <div class="form-group">
                                <div class="checkbox checkbox-primary no-margin-bottom">
                                    <input id="pForceOutgoingIp" name="force_outgoing_ip" type="checkbox" value="1" @if($egg->force_outgoing_ip) checked @endif />
                                    <label for="pForceOutgoingIp" class="strong">Force Outgoing IP</label>
                                    <p class="text-muted small">
                                        Forces all outgoing network traffic to have its Source IP NATed to the IP of the server's primary allocation IP.
                                        Required for certain games to work properly when the Node has multiple public IP addresses.
                                        <br>
                                        <strong>
                                            Enabling this option will disable internal networking for any servers using this egg,
                                            causing them to be unable to internally access other servers on the same node.
                                        </strong>
                                    </p>
                                </div>
                            </div>

                        </div>
                        <div class="col-sm-6">
                            <div class="form-group">
                                <label for="pDescription" class="control-label">Description</label>
                                <textarea id="pDescription" name="description" class="form-control" rows="8">{{ $egg->description }}</textarea>
                                <p class="text-muted small">A description of this Egg that will be displayed throughout the Panel as needed.</p>
                            </div>
                            <div class="form-group">
                                <label for="pStartup" class="control-label">Startup Command <span class="field-required"></span></label>
                                <textarea id="pStartup" name="startup" class="form-control" rows="8">{{ $egg->startup }}</textarea>
                                <p class="text-muted small">The default startup command that should be used for new servers using this Egg.</p>
                            </div>
                            <div class="form-group">
                                <label for="pConfigFeatures" class="control-label">Features</label>
                                <div>
                                    <select class="form-control" name="features[]" id="pConfigFeatures" multiple>
                                        @foreach(($egg->features ?? []) as $feature)
                                            <option value="{{ $feature }}" selected>{{ $feature }}</option>
                                        @endforeach
                                    </select>
                                    <p class="text-muted small">Additional features belonging to the egg. Useful for configuring additional panel modifications.</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xs-12">
            <div class="box">
                <div class="box-header with-border">
                    <h3 class="box-title">Process Management</h3>
                </div>
                <div class="box-body">
                    <div class="row">
                        <div class="col-xs-12">
                            <div class="alert alert-warning">
                                <p>The following configuration options should not be edited unless you understand how this system works. If wrongly modified it is possible for the daemon to break.</p>
                                <p>All fields are required unless you select a separate option from the 'Copy Settings From' dropdown, in which case fields may be left blank to use the values from that Egg.</p>
                            </div>
                        </div>
                        <div class="col-sm-6">
                            <div class="form-group">
                                <label for="pConfigFrom" class="form-label">Copy Settings From</label>
                                <select name="config_from" id="pConfigFrom" class="form-control">
                                    <option value="">None</option>
                                    @foreach($egg->nest->eggs as $o)
                                        <option value="{{ $o->id }}" {{ ($egg->config_from !== $o->id) ?: 'selected' }}>{{ $o->name }} &lt;{{ $o->author }}&gt;</option>
                                    @endforeach
                                </select>
                                <p class="text-muted small">If you would like to default to settings from another Egg select it from the menu above.</p>
                            </div>
                            <div class="form-group">
                                <label for="pConfigStop" class="form-label">Stop Command</label>
                                <input type="text" id="pConfigStop" name="config_stop" class="form-control" value="{{ $egg->config_stop }}" />
                                <p class="text-muted small">The command that should be sent to server processes to stop them gracefully. If you need to send a <code>SIGINT</code> you should enter <code>^C</code> here.</p>
                            </div>
                            <div class="form-group">
                                <label for="pConfigLogs" class="form-label">Log Configuration</label>
                                <textarea data-action="handle-tabs" id="pConfigLogs" name="config_logs" class="form-control" rows="6">{{ ! is_null($egg->config_logs) ? json_encode(json_decode($egg->config_logs), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) : '' }}</textarea>
                                <p class="text-muted small">This should be a JSON representation of where log files are stored, and whether or not the daemon should be creating custom logs.</p>
                            </div>
                        </div>
                        <div class="col-sm-6">
                            <div class="form-group">
                                <label for="pConfigFiles" class="form-label">Configuration Files</label>
                                <textarea data-action="handle-tabs" id="pConfigFiles" name="config_files" class="form-control" rows="6">{{ ! is_null($egg->config_files) ? json_encode(json_decode($egg->config_files), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) : '' }}</textarea>
                                <p class="text-muted small">This should be a JSON representation of configuration files to modify and what parts should be changed.</p>
                            </div>
                            <div class="form-group">
                                <label for="pConfigStartup" class="form-label">Start Configuration</label>
                                <textarea data-action="handle-tabs" id="pConfigStartup" name="config_startup" class="form-control" rows="6">{{ ! is_null($egg->config_startup) ? json_encode(json_decode($egg->config_startup), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) : '' }}</textarea>
                                <p class="text-muted small">This should be a JSON representation of what values the daemon should be looking for when booting a server to determine completion.</p>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="box-footer">
                    {!! csrf_field() !!}
                    <button type="submit" name="_method" value="PATCH" class="btn btn-primary btn-sm pull-right">Save</button>
                    <a href="{{ route('admin.nests.egg.export', $egg->id) }}" class="btn btn-sm btn-info pull-right" style="margin-right:10px;">Export</a>
                    <button id="deleteButton" type="submit" name="_method" value="DELETE" class="btn btn-danger btn-sm muted muted-hover">
                        <i class="fa fa-trash-o"></i>
                    </button>
                </div>
            </div>
        </div>
    </div>
</form>
{{-- Updates --}}
@php
    $hasUpdateUrl = !empty($egg->update_url);
    $hasUpdate = $egg->last_update_hash && $egg->applied_update_hash && $egg->last_update_hash !== $egg->applied_update_hash;
    $ovr = $egg->update_overrides ?? [];
    $initialStatus = $egg->exclude_from_updates ? ['label' => 'default', 'text' => 'Excluded']
        : ($egg->last_update_error ? ['label' => 'danger', 'text' => 'Error']
        : ($hasUpdate ? ['label' => 'warning', 'text' => 'Update Available']
        : ($egg->last_update_check_at ? ['label' => 'success', 'text' => 'Up to Date']
        : ['label' => 'info', 'text' => 'Not Checked'])));
@endphp
<div class="row" id="eggUpdates">
    <div class="col-xs-12">
        <div class="box box-solid">
            <div class="box-header with-border">
                <h3 class="box-title">Updates</h3>
                <div class="box-tools pull-right">
                    <span id="updateStatusBadge" class="label label-{{ $initialStatus['label'] }}">{{ $initialStatus['text'] }}</span>
                </div>
            </div>
            <div class="box-body">
                <div class="row">
                    {{-- LEFT: Manual Update + Overrides --}}
                    <div class="col-sm-6">
                        {{-- Manual Upload --}}
                        <div class="form-group" style="margin-bottom:8px;">
                            <label class="text-muted" style="font-size:12px;text-transform:uppercase;letter-spacing:.5px;">Manual Update</label>
                            <p class="text-muted small" style="margin-bottom:8px;">Upload a new egg JSON to replace settings. Existing servers are not affected.</p>
                            <form id="manualUpdateForm" data-url="{{ route('admin.nests.egg.view', $egg->id) }}">
                                @csrf
                                <input type="hidden" name="_method" value="PUT">
                                <div class="input-group input-group-sm">
                                    <input type="file" name="import_file" class="form-control" style="padding:3px 6px;height:auto;">
                                    <span class="input-group-btn">
                                        <button id="btnManualUpdate" type="button" class="btn btn-danger btn-flat"><i class="fa fa-upload"></i> Update</button>
                                    </span>
                                </div>
                            </form>
                        </div>
                        @if($hasUpdateUrl)
                        <hr style="margin:12px 0;">
                        {{-- Overrides --}}
                        <div class="form-group" style="margin-bottom:0;">
                            <label class="text-muted" style="font-size:12px;text-transform:uppercase;letter-spacing:.5px;">Overrides</label>
                            <p class="text-muted small" style="margin-bottom:8px;">Pin local values that persist across remote updates.</p>
                            <form id="overrideForm" data-url="{{ route('admin.nests.egg.view', $egg->id) }}">
                                @csrf
                                <input type="hidden" name="_method" value="PATCH">
                                <div class="row">
                                    <div class="col-xs-6" style="padding-right:4px;">
                                        <input type="text" id="pOvrName" name="update_overrides[name]" value="{{ $ovr['name'] ?? '' }}" class="form-control input-sm" placeholder="Override name">
                                    </div>
                                    <div class="col-xs-6" style="padding-left:4px;">
                                        <input type="text" id="pOvrDesc" name="update_overrides[description]" value="{{ $ovr['description'] ?? '' }}" class="form-control input-sm" placeholder="Override description">
                                    </div>
                                </div>
                                <div class="input-group input-group-sm" style="margin-top:6px;">
                                    <input type="url" id="pOvrUrl" name="update_overrides[update_url]" value="{{ $ovr['update_url'] ?? '' }}" class="form-control" placeholder="Override update URL">
                                    <span class="input-group-btn">
                                        <button id="btnSaveOverrides" type="button" class="btn btn-primary btn-flat"><i class="fa fa-save"></i> Save</button>
                                    </span>
                                </div>
                            </form>
                        </div>
                        @endif
                    </div>

                    {{-- RIGHT: Status + Actions --}}
                    <div class="col-sm-6">
                        @if($hasUpdateUrl)
                        <div class="row" style="margin-bottom:10px;">
                            <div class="col-xs-6">
                                <label class="text-muted" style="font-size:11px;text-transform:uppercase;letter-spacing:.5px;">Last Checked</label>
                                <p class="form-control-static" id="updateLastChecked" style="padding-top:0;margin-bottom:0;font-size:13px;">{{ $egg->last_update_check_at ? $egg->last_update_check_at->toDayDateTimeString() : '—' }}</p>
                            </div>
                            <div class="col-xs-6">
                                <label class="text-muted" style="font-size:11px;text-transform:uppercase;letter-spacing:.5px;">Last Applied</label>
                                <p class="form-control-static" id="updateLastApplied" style="padding-top:0;margin-bottom:0;font-size:13px;">{{ $egg->last_update_applied_at ? $egg->last_update_applied_at->toDayDateTimeString() : '—' }}</p>
                            </div>
                        </div>
                        <div class="btn-group btn-group-justified" role="group" id="updateActions" style="margin-bottom:12px;">
                            <a class="btn btn-info btn-sm" id="btnCheckUpdate" data-url="{{ route('admin.nests.egg.check_update', $egg->id) }}">
                                <i class="fa fa-refresh"></i> Check
                            </a>
                            @if($hasUpdate)
                            <a class="btn btn-success btn-sm" id="btnApplyUpdate" data-url="{{ route('admin.nests.egg.apply_update', $egg->id) }}">
                                <i class="fa fa-cloud-download"></i> Apply
                            </a>
                            @endif
                        </div>
                        <div id="updateInfo">
                            @if($egg->last_update_error)
                            <div class="callout callout-danger" style="margin-bottom:8px;padding:6px 10px;font-size:12px;">
                                <i class="fa fa-exclamation-circle"></i>
                                {{ $egg->last_update_error }}
                            </div>
                            @endif
                            @if($egg->last_update_hash)
                            <div class="form-group" style="margin-bottom:6px;">
                                <label class="text-muted" style="font-size:11px;text-transform:uppercase;letter-spacing:.5px;">Content Hash</label>
                                <p class="form-control-static" style="padding-top:0;margin-bottom:0;"><code style="font-size:11px;">{{ $egg->last_update_hash }}</code></p>
                            </div>
                            @endif
                            <div id="updateDiffArea">
                            </div>
                        </div>
                        @else
                        <div class="callout callout-info" style="margin-bottom:0;">
                            <p style="margin-bottom:4px;">No <code>update_url</code> set.</p>
                            <p class="small" style="margin-bottom:0;">Configure it above, or use <strong>Manual Update</strong> to upload an egg JSON directly.</p>
                        </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('footer-scripts')
    @parent
    <script>
    // Existing handlers
    $('#pConfigFrom').select2();
    $('#deleteButton').on('mouseenter', function (event) {
        $(this).find('i').html(' Delete Egg');
    }).on('mouseleave', function (event) {
        $(this).find('i').html('');
    });
    $('textarea[data-action="handle-tabs"]').on('keydown', function(event) {
        if (event.keyCode === 9) {
            event.preventDefault();

            var curPos = $(this)[0].selectionStart;
            var prepend = $(this).val().substr(0, curPos);
            var append = $(this).val().substr(curPos);

            $(this).val(prepend + '    ' + append);
        }
    });
    $('#pConfigFeatures').select2({
        tags: true,
        selectOnClose: false,
        tokenSeparators: [',', ' '],
    });

    // Updates AJAX
    (function() {
        var $updates = $('#eggUpdates');
        if (!$updates.length) return;

        var EGG_ID = @json($egg->id);
        var VIEW_URL = @json(route('admin.nests.egg.view', $egg->id));
        var CHECK_URL = @json(route('admin.nests.egg.check_update', $egg->id));
        var APPLY_URL = @json(route('admin.nests.egg.apply_update', $egg->id));

        var $badge = $('#updateStatusBadge');
        var $lastChecked = $('#updateLastChecked');
        var $lastApplied = $('#updateLastApplied');
        var $diffArea = $('#updateDiffArea');
        var $actions = $('#updateActions');
        var $updateInfo = $('#updateInfo');
        var $overrideForm = $('#overrideForm');
        var $manualForm = $('#manualUpdateForm');

        function token() {
            return $('meta[name="csrf-token"]').attr('content') || $('[name="_token"]').first().val();
        }

        function loading(btn, on) {
            var $b = $(btn); if (!$b.length) return;
            if (on) { $b.data('html', $b.html()).prop('disabled', true).find('i').attr('class', 'fa fa-spinner fa-spin'); }
            else { $b.html($b.data('html')).prop('disabled', false); }
        }

        function badge(type, text) {
            if (!$badge.length) return;
            var cls = {success:'label-success', warning:'label-warning', danger:'label-danger', info:'label-info', default:'label-default'};
            $badge.attr('class', 'label ' + (cls[type]||'label-info')).text(text);
        }

        function notify(msg, type) {
            var cls = type === 'error' ? 'alert-danger' : type === 'success' ? 'alert-success' : type === 'warning' ? 'alert-warning' : 'alert-info';
            var $a = $('<div class="alert ' + cls + ' alert-dismissible" style="margin:0 0 10px 0;padding:8px 12px;font-size:13px;"><button type="button" class="close" data-dismiss="alert">&times;</button>' + $('<span>').text(msg).html() + '</div>');
            $updates.find('.box-body > .row').first().before($a);
            setTimeout(function() { $a.alert('close'); }, 5000);
        }

        // --- Manual Upload ---
        $manualForm.length && $('#btnManualUpdate').on('click', function() {
            var file = $manualForm.find('[name="import_file"]')[0];
            if (!file || !file.files || !file.files[0]) { notify('Select a file first.', 'error'); return; }
            loading(this, true);
            var fd = new FormData($manualForm[0]);
            fd.append('_method', 'PUT');
            $.ajax({url: VIEW_URL, method: 'POST', data: fd, processData: false, contentType: false})
                .done(function(r) { notify(r.message || 'Egg updated.', 'success'); })
                .fail(function(xhr) { notify((xhr.responseJSON&&xhr.responseJSON.message)||'Upload failed.', 'error'); })
                .always(function() { loading($('#btnManualUpdate'), false); });
        });

        // --- Check Updates ---
        var $btnCheck = $('#btnCheckUpdate');
        $btnCheck.length && $btnCheck.on('click', function() {
            loading(this, true);
            badge('info', 'Checking...');
            $diffArea.empty();

            $.post(CHECK_URL, {_token: token()})
                .done(function(r) {
                    $updateInfo.find('.callout-danger').remove(); // clear persistent error
                    if (r.status === 'update_available') {
                        badge('warning', 'Update Available');
                        notify(r.message, 'warning');
                        $diffArea.html('<div class="form-group" style="margin-bottom:0;"><label class="text-muted" style="font-size:11px;text-transform:uppercase;letter-spacing:.5px;">Changes Detected</label><pre style="max-height:180px;overflow-y:auto;font-size:11px;margin-bottom:0;">' + $('<span>').text(JSON.stringify(r.diff, null, 4)).html() + '</pre></div>');
                        if (!$('#btnApplyUpdate').length) {
                            $actions.append('<a class="btn btn-success btn-sm" id="btnApplyUpdate" data-url="' + APPLY_URL + '"><i class="fa fa-cloud-download"></i> Apply</a>');
                        }
                    } else if (r.status === 'up_to_date') {
                        badge('success', 'Up to Date');
                        notify(r.message, 'success');
                    } else {
                        badge('danger', 'Error');
                        notify(r.message, 'error');
                        // show persistent error inline
                        $updateInfo.prepend('<div class="callout callout-danger" style="margin-bottom:8px;padding:6px 10px;font-size:12px;"><i class="fa fa-exclamation-circle"></i> ' + $('<span>').text(r.error || r.message).html() + '</div>');
                    }
                    $lastChecked.text(new Date().toLocaleString());
                })
                .fail(function(xhr) {
                    badge('danger', 'Error');
                    var errMsg = (xhr.responseJSON&&xhr.responseJSON.message)||'Check failed.';
                    notify(errMsg, 'error');
                    $updateInfo.prepend('<div class="callout callout-danger" style="margin-bottom:8px;padding:6px 10px;font-size:12px;"><i class="fa fa-exclamation-circle"></i> ' + $('<span>').text(errMsg).html() + '</div>');
                })
                .always(function() { loading($btnCheck, false); });
        });

        // --- Apply Update ---
        function bindApply() {
            var $btn = $('#btnApplyUpdate');
            if (!$btn.length) return;
            $btn.off('click').on('click', function() {
                loading(this, true);
                $.post($(this).data('url') || APPLY_URL, {_token: token()})
                    .done(function(r) {
                        if (r.status === 'applied') {
                            badge('success', 'Up to Date');
                            notify(r.message, 'success');
                            $lastApplied.text(new Date().toLocaleString());
                            $diffArea.empty();
                            $('#btnApplyUpdate').remove();
                        } else { notify(r.message, 'error'); }
                    })
                    .fail(function(xhr) { notify((xhr.responseJSON&&xhr.responseJSON.message)||'Apply failed.', 'error'); })
                    .always(function() { loading($('#btnApplyUpdate'), false); });
            });
        }
        bindApply();
        // Re-bind when check adds a new Apply button
        $actions.length && new MutationObserver(function() { bindApply(); }).observe($actions[0], {childList: true});

        // --- Save Overrides ---
        $overrideForm.length && $('#btnSaveOverrides').on('click', function() {
            loading(this, true);
            $.post(VIEW_URL, $overrideForm.serialize())
                .done(function() { notify('Overrides saved.', 'success'); })
                .fail(function(xhr) { notify((xhr.responseJSON&&xhr.responseJSON.message)||'Failed.', 'error'); })
                .always(function() { loading($('#btnSaveOverrides'), false); });
        });
    })();
    </script>
@endsection
