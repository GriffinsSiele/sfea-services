all:
	@go build -mod=vendor -o ./bin/console .

clean:
	@rm bin/console

lint:
	@golangci-lint run
	@gocritic check ./...

test:
	@go test . -v -count=1 -failfast
