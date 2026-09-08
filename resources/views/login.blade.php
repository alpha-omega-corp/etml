<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Der Mensch — Connexion</title>
@include('partials._style')
</head>
<body>

<div class="masthead">
  <div class="kicker">Vocabulaire Allemand · I. Der Mensch</div>
  <h1>Personalien &amp; Familie</h1>
</div>

<form class="login-box" method="POST" action="{{ route('login') }}">
  @csrf
  <label for="username">Votre nom</label>
  <input id="username" name="username" type="text" autocomplete="username" autofocus
         value="{{ old('username') }}" placeholder="p. ex. anna">
  <button type="submit" class="primary">commencer</button>

  @error('username')
    <div class="error-note">{{ $message }}</div>
  @enderror

  <div class="login-note">
    Pas de mot de passe : votre nom sert uniquement à retrouver vos cartes
    « je sais » et « à revoir ».
  </div>
</form>

</body>
</html>
