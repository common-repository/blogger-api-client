VERSION=0.2

all: bac-$(VERSION).zip

bac-$(VERSION).zip: README INSTALL bac-data.sql ixr.bloggerclient.php bac.php
	mkdir bac
	cp $^ bac
	zip $@ bac/*
	rm -rf bac
