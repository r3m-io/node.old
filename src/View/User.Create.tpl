Create User:

{{$email = terminal.readline('Email:')}}

{{$password = terminal.readline('Password:', 'hidden')}}

{{$password_confirmation = terminal.readline('Password Confirmation:', 'hidden')}}


{{$user = User.create(email: $email, password: $password, password_confirmation: $password_confirmation, name: $name, role: $role)}}

{{$user.save}}

{{$user}}