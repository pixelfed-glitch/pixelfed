@extends('site.partial.template')

@section('section')

  <div class="title">
    <h3 class="font-weight-bold">Open Source</h3>
  </div>
  <hr>
  <section>
    <p class="lead">The software that powers this website is called <a href="https://github.com/pixelfed-glitch/pixelfed">Pixelfed Glitch</a> and anyone can <a href="https://github.com/pixelfed-glitch/pixelfed">download</a> or <a href="https://github.com/pixelfed-glitch/pixelfed">view</a> the source code and run their own instance!</p>
  </section>
@endsection

@push('meta')
<meta property="og:description" content="Open source in Pixelfed Glitch">
@endpush
