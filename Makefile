NAME = upmada-app
CONTAINER := `docker ps -q --filter "ancestor=$(NAME)"`

build:
	docker build -t $(NAME) .
start:
	docker run -d -p80:80 -v `pwd`/src:/var/www/html  $(NAME):latest
stop:
	docker stop $(CONTAINER)
clean:
	docker system prune -a
ps:
	docker ps --filter "ancestor=$(NAME)"
bash:
	docker exec -it $(CONTAINER) /bin/bash
