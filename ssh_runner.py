import pty
import os
import sys
import time
import select

def run_ssh_command(command):
    pid, fd = pty.fork()

    if pid == 0:
        # Child process
        # Use -t to force pseudo-tty allocation which is often needed for interactive programs or sudo
        # But for simple command execution, we might not want -t if we want clean output. 
        # However, pty.fork() already gives us a tty.
        os.execvp("ssh", ["ssh", "-o", "StrictHostKeyChecking=no", "smknbuki@64.120.92.52", command])
    else:
        # Parent process
        output_buffer = b""
        password_sent = False
        
        while True:
            try:
                r, w, e = select.select([fd], [], [], 10) # 10s timeout
                if not r:
                    break
                    
                chunk = os.read(fd, 1024)
                if not chunk:
                    break
                    
                output_buffer += chunk
                
                if b"password:" in output_buffer and not password_sent:
                    os.write(fd, b"@j4r1ng4nSTM\n")
                    password_sent = True
                    
            except OSError:
                break
                
        os.close(fd)
        # Filter out the password prompt line from output if possible for cleaner logs
        return output_buffer.decode(errors='ignore')

if __name__ == "__main__":
    if len(sys.argv) < 2:
        print("Usage: python3 ssh_runner.py <command>")
        sys.exit(1)
        
    cmd = " ".join(sys.argv[1:])
    print(run_ssh_command(cmd))
