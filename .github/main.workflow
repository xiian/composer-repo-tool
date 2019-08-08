workflow "Run Unit Tests" {
  on = "label"
  resolves = ["pxgamer/composer-action@v1.0.1"]
}

action "pxgamer/composer-action@v1.0.1" {
  uses = "pxgamer/composer-action@v1.0.1"
  args = "install"
}
