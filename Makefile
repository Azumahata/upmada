NAME=upmada-app

build:
	docker build -t $(NAME) .
start:
	docker run -d -p80:80 -v `pwd`/src:/var/www/html  $(NAME):latest
stop:
	docker ps -q --filter "ancestor=$(NAME)" | xargs docker stop
clean:
	docker system prune -a
ps:
	docker ps --filter "ancestor=$(NAME)"
bash:
	CONTAINER=$(docker ps -q --filter "ancestor=$(NAME)") && docker exec "${CONTAINER}" /bin/bash
