const MySQLEvents = require("@rodrigogs/mysql-events");
const server = require("http").createServer();
const io = require("socket.io")(server);

io.on("connection", client => {
    console.log("Connected", client.id);

    client.on("disconnect", () => {
        console.log("Disconnected", client.id);
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
    const debounce = null;
    let data = [];

    instance.addTrigger({
        name: "Whole database instance",
        expression: "stacks.stk_activities",
        statement: MySQLEvents.STATEMENTS.ALL,
        onEvent: event => {
            console.log(event);

            event.affectedRows.forEach(row => {
                data.push(row.after);
            });

            if (debounce) {
                clearTimeout(debounce);
                debounce = null;
            }

            debounce = setTimeout(() => {
                if (data.length) {
                    io.emit("update", data);
                    data = [];
                }
            }, 2000);
        },
    });

    instance.on(MySQLEvents.EVENTS.CONNECTION_ERROR, console.error);
    instance.on(MySQLEvents.EVENTS.ZONGJI_ERROR, console.error);
};

program()
    .then(() => console.log("Waiting for database vents..."))
    .catch(console.error);
