const MySQLEvents = require("@rodrigogs/mysql-events");
const server = require("http").createServer();
const io = require("socket.io")(server);

const clients = {};

io.on("connection", client => {
    client.on("event", data => {
        /* … */
    });
    client.on("disconnect", () => {
        /* … */
    });
});
server.listen(3333);

const program = async () => {
    const instance = new MySQLEvents(
        {
            host: "127.0.0.1",
            user: "root",
            password: "root",
            port: "8889",
        },
        {
            startAtEnd: true,
        }
    );

    await instance.start();

    instance.addTrigger({
        name: "Whole database instance",
        expression: "stacks.stk_activities",
        statement: MySQLEvents.STATEMENTS.ALL,
        onEvent: event => {
            console.log(event);
        },
    });

    instance.on(MySQLEvents.EVENTS.CONNECTION_ERROR, console.error);
    instance.on(MySQLEvents.EVENTS.ZONGJI_ERROR, console.error);
};

program()
    .then(() => console.log("Waiting for database vents..."))
    .catch(console.error);
