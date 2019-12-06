<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>Custom Mail Portal With Sendgrid</title>

    <!-- Styles -->
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.2.1/css/bootstrap.min.css" rel="stylesheet"
        integrity="sha384-GJzZqFGwb1QTTN6wy59ffF1BuGJpLSa9DkKMp0DgiMDm4iYMj70gZWKYbI706tWS" crossorigin="anonymous">

</head>

<body>
    <div class="container">
        <div class="jumbotron">
            @if (session('success'))
            <div class="alert alert-success">
                {{ session('success') }}
            </div>
            @endif
            @if ($errors->any())
            <div class="alert alert-danger">
                <ul>
                    @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
            @endif
            <div class="row">

                <div class="col">
                    <div class="card">
                        <div class="card-header">
                            Send Custom Mail
                        </div>
                        <div class="card-body">
                            <form method="POST" action="/sendmail">
                                @csrf
                                <div class="form-group">
                                    <label>From</label>
                                    <input required name="from" value="{{getenv('MAIL_FROM')}}" class="form-control">
                                </div>
                                <div class="form-group">
                                    <label>Subject</label>
                                    <input required name="subject" class="form-control">
                                </div>
                                <div class="form-group">
                                    <label>Select users to send mail to</label>
                                    <select required name="users[]" multiple class="form-control">
                                        @foreach ($users as $user)
                                        <option value="{{$user}}">{{$user->name}} - {{$user->email}}</option>
                                        @endforeach

                                    </select>
                                </div>
                                <div class="form-group">
                                    <label>Message</label>
                                    <textarea required name="body" class="form-control" rows="3"></textarea>
                                </div>
                                <button type="submit" class="btn btn-primary">Send Mail(s)</button>

                            </form>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>
</body>

</html>
