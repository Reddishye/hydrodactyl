@extends('layouts.admin')
@include('partials/admin.settings.nav', ['activeTab' => 'mail'])

@section('title')
  Mail Settings
@endsection

@section('content-header')
  <h1>Mail Settings<small>Configure how Pterodactyl should handle sending emails.</small></h1>
  <ol class="breadcrumb">
    <li><a href="{{ route('admin.index') }}">Admin</a></li>
    <li class="active">Settings</li>
  </ol>
@endsection

@section('content')
  @yield('settings::nav')
  <div class="row">
    <div class="col-md-8 col-md-offset-2">
      @if($disabled)
      <div class="box box-primary">
        <div class="box-header with-border">
          <i class="fa fa-envelope"></i> <h3 class="box-title" style="display:inline;">Mail Settings</h3>
        </div>
        <div class="box-body">
          <div class="alert alert-info no-margin-bottom">
            <i class="fa fa-info-circle"></i> This interface requires the <code>smtp</code> mail driver. Use
            <code>php artisan p:environment:mail</code> or set <code>MAIL_DRIVER=smtp</code> in your environment file.
          </div>
        </div>
      </div>
      @else
      <div class="box box-primary">
        <div class="box-header with-border">
          <i class="fa fa-envelope"></i> <h3 class="box-title" style="display:inline;">SMTP Settings</h3>
        </div>
        <form>
        <div class="box-body">
          <div class="row">
            <div class="form-group col-md-6">
              <label class="control-label">SMTP Host</label>
              <input required type="text" class="form-control" name="mail:mailers:smtp:host"
                value="{{ old('mail:mailers:smtp:host', config('mail.mailers.smtp.host')) }}" />
              <p class="text-muted small" style="margin-top:4px;">SMTP server address.</p>
            </div>
            <div class="form-group col-md-3">
              <label class="control-label">Port</label>
              <input required type="number" class="form-control" name="mail:mailers:smtp:port"
                value="{{ old('mail:mailers:smtp:port', config('mail.mailers.smtp.port')) }}" />
              <p class="text-muted small" style="margin-top:4px;">SMTP server port.</p>
            </div>
            <div class="form-group col-md-3">
              <label class="control-label">Encryption</label>
              @php
                $encryption = old('mail:mailers:smtp:encryption', config('mail.mailers.smtp.encryption'));
              @endphp
              <select name="mail:mailers:smtp:encryption" class="form-control">
                <option value="" @if($encryption === '') selected @endif>None</option>
                <option value="tls" @if($encryption === 'tls') selected @endif>TLS</option>
                <option value="ssl" @if($encryption === 'ssl') selected @endif>SSL</option>
              </select>
              <p class="text-muted small" style="margin-top:4px;">Encryption protocol.</p>
            </div>
          </div>
          <div class="row">
            <div class="form-group col-md-6">
              <label class="control-label">Username <span class="field-optional"></span></label>
              <input type="text" class="form-control" name="mail:mailers:smtp:username"
                value="{{ old('mail:mailers:smtp:username', config('mail.mailers.smtp.username')) }}" />
              <p class="text-muted small" style="margin-top:4px;">SMTP authentication username.</p>
            </div>
            <div class="form-group col-md-6">
              <label class="control-label">Password <span class="field-optional"></span></label>
              <input type="password" class="form-control" name="mail:mailers:smtp:password" />
              <p class="text-muted small" style="margin-top:4px;">Leave blank to keep the existing password. Enter <code>!e</code> to set an empty password.</p>
            </div>
          </div>
          <hr />
          <div class="row">
            <div class="form-group col-md-6">
              <label class="control-label">Mail From Address</label>
              <input required type="email" class="form-control" name="mail:from:address"
                value="{{ old('mail:from:address', config('mail.from.address')) }}" />
              <p class="text-muted small" style="margin-top:4px;">All outgoing emails will use this address.</p>
            </div>
            <div class="form-group col-md-6">
              <label class="control-label">Mail From Name <span class="field-optional"></span></label>
              <input type="text" class="form-control" name="mail:from:name"
                value="{{ old('mail:from:name', config('mail.from.name')) }}" />
              <p class="text-muted small" style="margin-top:4px;">Display name for outgoing emails.</p>
            </div>
          </div>
        </div>
        <div class="box-footer">
          {{ csrf_field() }}
          <div class="pull-right" style="display:flex;gap:6px;">
            <button type="button" id="testButton" class="btn btn-success"><i class="fa fa-paper-plane"></i> Test</button>
            <button type="button" id="saveButton" class="btn btn-primary"><i class="fa fa-save"></i> Save</button>
          </div>
        </div>
        </form>
      </div>
      @endif
    </div>
  </div>
@endsection

@section('footer-scripts')
  @parent

  <script>
    function saveSettings() {
    return $.ajax({
      method: 'PATCH',
      url: '/admin/settings/mail',
      contentType: 'application/json',
      data: JSON.stringify({
      'mail:mailers:smtp:host': $('input[name="mail:mailers:smtp:host"]').val(),
      'mail:mailers:smtp:port': $('input[name="mail:mailers:smtp:port"]').val(),
      'mail:mailers:smtp:encryption': $('select[name="mail:mailers:smtp:encryption"]').val(),
      'mail:mailers:smtp:username': $('input[name="mail:mailers:smtp:username"]').val(),
      'mail:mailers:smtp:password': $('input[name="mail:mailers:smtp:password"]').val(),
      'mail:from:address': $('input[name="mail:from:address"]').val(),
      'mail:from:name': $('input[name="mail:from:name"]').val()
      }),
      headers: { 'X-CSRF-Token': $('input[name="_token"]').val() }
    }).fail(function (jqXHR) {
      showErrorDialog(jqXHR, 'save');
    });
    }

    function testSettings() {
    swal({
      type: 'info',
      title: 'Test Mail Settings',
      text: 'Click "Test" to begin the test.',
      showCancelButton: true,
      confirmButtonText: 'Test',
      closeOnConfirm: false,
      showLoaderOnConfirm: true
    }, function () {
      $.ajax({
      method: 'POST',
      url: '/admin/settings/mail/test',
      headers: { 'X-CSRF-TOKEN': $('input[name="_token"]').val() }
      }).fail(function (jqXHR) {
      showErrorDialog(jqXHR, 'test');
      }).done(function () {
      swal({
        title: 'Success',
        text: 'The test message was sent successfully.',
        type: 'success'
      });
      });
    });
    }

    function saveAndTestSettings() {
    saveSettings().done(testSettings);
    }

    function showErrorDialog(jqXHR, verb) {
    console.error(jqXHR);
    var errorText = '';
    if (!jqXHR.responseJSON) {
      errorText = jqXHR.responseText;
    } else if (jqXHR.responseJSON.error) {
      errorText = jqXHR.responseJSON.error;
    } else if (jqXHR.responseJSON.errors) {
      $.each(jqXHR.responseJSON.errors, function (i, v) {
      if (v.detail) {
        errorText += v.detail + ' ';
      }
      });
    }

    swal({
      title: 'Whoops!',
      text: 'An error occurred while attempting to ' + verb + ' mail settings: ' + errorText,
      type: 'error'
    });
    }

    $(document).ready(function () {
    $('#testButton').on('click', saveAndTestSettings);
    $('#saveButton').on('click', function () {
      saveSettings().done(function () {
      swal({
        title: 'Success',
        text: 'Mail settings have been updated successfully and the queue worker was restarted to apply these changes.',
        type: 'success'
      });
      });
    });
    });
  </script>
@endsection