import pty
import os
import sys
import time

def read_until(fd, marker):
    buffer = b""
    while True:
        try:
            chunk = os.read(fd, 1024)
            if not chunk:
                break
            buffer += chunk
            if marker in buffer:
                return buffer
        except OSError:
            break
    return buffer

pid, fd = pty.fork()

if pid == 0:
    # Child process
    os.execvp("ssh", ["ssh", "-o", "StrictHostKeyChecking=no", "smknbuki@64.120.92.52", "echo 'SSH_SUCCESS'"])
else:
    # Parent process
    try:
        # Wait for password prompt
        output = read_until(fd, b"password:")
        
        # Send password
        os.write(fd, b"@j4r1ng4nSTM\n")
        
        # Read result
        result = read_until(fd, b"SSH_SUCCESS")
        
        if b"SSH_SUCCESS" in result:
            print("Access Verified")
        else:
            print("Access Failed")
            print(result.decode(errors='ignore'))
            
    except Exception as e:
        print(f"Error: {e}")
    finally:
        os.close(fd)
