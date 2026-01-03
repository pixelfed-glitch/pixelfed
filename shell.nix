{ pkgs ? import <nixpkgs> {} }:
pkgs.mkShell {
  name = "pixelfed-nix-shell";
  buildInputs = with pkgs; [
    act
    docker
    php84
    php84Packages.composer
    (pkgs.php84.buildEnv {
      extensions = ({ enabled, all }: enabled ++ (with all; [
        # bcmath
        # curl
        # gd
        # imagick
        # intl
        # mbstring
        # redis
        # vips
        # xml
        # yaml
        # zip
        ffi
        xdebug
      ]));
      extraConfig = ''
        xdebug.mode=debug
      '';
    })
    nodejs
    nodePackages.npm
    mkcert
    ddev
    hadolint
    dgoss
    gh
  ];
  runScript = "$SHELL";
  shellHook = ''
      mkcert -install
      export PATH="$PWD/node_modules/.bin/:$PATH"
      export PATH="$PWD/vendor/bin/:$PATH"
  '';
}
