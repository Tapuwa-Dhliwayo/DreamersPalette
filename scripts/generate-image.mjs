import { Console } from "node:console";
import { InferenceClient, setLogger } from "@huggingface/inference";

// Standard output carries image bytes only; SDK diagnostics must not corrupt them.
setLogger(new Console({ stdout: process.stderr, stderr: process.stderr }));

// The official SDK resolves partner-provider request and response formats.
let input = "";
for await (const chunk of process.stdin) input += chunk;
try {
    const { token, model, prompt } = JSON.parse(input);
    const image = await new InferenceClient(token).textToImage({
        model, provider: "auto", inputs: prompt,
    });
    process.stdout.write(Buffer.from(await image.arrayBuffer()));
} catch {
    process.stderr.write("Image provider request failed.\n");
    process.exitCode = 1;
}
